<?php

namespace Vectorface\Auth;

use Psr\Log\LoggerAwareTrait;
use Vectorface\Auth\Plugin\AuthPluginInterface;
use \Exception;

/**
 * A class representing a loose Authentication and Authorization framework.
 *  - Authentication is handled explicitly with login, logout, and verify functions.
 *  - Authorization is handled in whatever capacity is implemented by loaded plugins.
 *
 * Plugin classes can share data using the Auth class itself as a shared source of data.
 */
class Auth implements \ArrayAccess
{
    /**
     * Provides setLogger method, and protected logger property.
     */
    use LoggerAwareTrait;

    /**
     * Name of the method called when authenticating.
     */
    const ACTION_LOGIN = 'login';

    /**
     * Name of the method called when attempting to log out.
     */
    const ACTION_LOGOUT = 'logout';

    /**
     * Name of the session verification method.
     */
    const ACTION_VERIFY = 'verify';

    /**
     * A result that has no effect either positive or negative.
     */
    const RESULT_NOOP = null;

    /**
     * A result that acts as a provisional success; Pass if all other tests pass.
     */
    const RESULT_SUCCESS = 0;

    /**
     * A result that indicates forced immediate success, bypassing further tests. Should not be used lightly.
     */
    const RESULT_FORCE = 1;

    /**
     * A result that indicates failure. All failures are immediate.
     */
    const RESULT_FAILURE = -1;

    /**
     * An array of auth plugins.
     *
     * @var AuthPluginInterface[]
     */
    private $plugins = array();

    /**
     * Shared values, accessible using the ArrayAccess interface.
     *
     * @var mixed[]
     */
    private $shared = array();

    /**
     * Add a plugin to the Auth module.
     *
     * @param string|AuthPluginInterface The name of a plugin class to be registered, or a configured instance of a
     *                                   security plgin.
     * @return bool True if the plugin was added successfully.
     */
    public function addPlugin($plugin)
    {
        if (!($plugin instanceof AuthPluginInterface)) {
            $interfaceName = 'Vectorface\\Auth\\Plugin\\AuthPluginInterface';
            if (!is_string($plugin) || !in_array($interfaceName, class_implements($plugin, false))) {
                return false;
            }
            $plugin = new $plugin();
        }
        $plugin->setAuth($this);
        $this->plugins[] = $plugin;
        return true;
    }

    /**
     * Perform a login based on username and password.
     *
     * @param string $username A user identifier.
     * @param string $password The user's password.
     * @return bool True if the login was successful, false otherwise.
     */
    public function login($username, $password)
    {
        return $this->action(self::ACTION_LOGIN, array($username, $password));
    }

    /**
     * Attempt to log the user out.
     */
    public function logout()
    {
        return $this->action(self::ACTION_LOGOUT);
    }

    /**
     * Verify a user's saved data - check if the user is logged in.
     *
     * @return bool True if the user's session was verified successfully.
     */
    public function verify()
    {
        return $this->action(self::ACTION_VERIFY);
    }

    /**
     * Perform a generalized action: login, logout, or verify: Run said function on each plugin in order verifying
     * result along the way.
     *
     * @param string $action The action to be taken. One of login, logout, or verify.
     * @return bool True if the action was successful, false otherwise.
     */
    private function action($action, $arguments = array())
    {
        /* This is a bit of defensive programming; It is not possible to hit this code. */
        // @codeCoverageIgnoreStart
        if (!in_array($action, array(self::ACTION_LOGIN, self::ACTION_LOGOUT, self::ACTION_VERIFY))) {
            throw new AuthException("Attempted to perform unknown action: $action");
        }
        // @codeCoverageIgnoreEnd

        $success = false;

        foreach ($this->plugins as $plugin) {
            try {
                $result = call_user_func_array(array($plugin, $action), $arguments);
            } catch (AuthException $e) {
                throw $e;
            } catch (\Exception $e) {
                error_log(sprintf(
                    "Fatal %s error in %s plugin: %s (%s@%s)",
                    $action,
                    get_class($plugin),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ));
                return false;
            }

            /* Expected results are an integer or null; Anything else is incorrect. */
            if (!(is_int($result) || $result === null)) {
                throw new AuthException(sprintf(
                    "Unknown %s result in %s plugin: %s",
                    $action,
                    get_class($plugin),
                    print_r($result, true)
                ));
            }

            if ($result === self::RESULT_NOOP) {
                // Do nothing, not defined for plugin.
            } elseif ($result === self::RESULT_SUCCESS /* 0 */) {
                $success = true;
            } elseif ($result >= self::RESULT_FORCE /* 1 */) {
                return true;
            } else /* if ($result <= self::RESULT_FAILURE) */ {
                return false;
            }
        }
        return $success;
    }

    /**
     * Passthrough function call for plugins.
     *
     * @param string $method The name of the method to be called.
     * @param string $args An array of arguments to be passed to the method.
     * @return mixed Returns whatever the passthrough function returns, or null or error or missing function.
     */
    public function __call($method, $args = array())
    {
        foreach ($this->plugins as $plugin) {
            if (is_callable(array($plugin, $method))) {
                try {
                    return call_user_func_array(array($plugin, $method), $args);
                } catch (AuthException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    error_log(sprintf(
                        "Auth Error: Exception caught calling %s->%s: %s",
                        get_class($plugin),
                        $method,
                        $e->getMessage()
                    ));
                    return;
                }
            }
        }

        error_log(__CLASS__ . ": $method not implemented by any loaded plugin");
    }

    /**
     * ArrayAccess offsetExists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset)
    {
        return isset($this->shared[$offset]);
    }

    /**
     * ArrayAccess offsetGet
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     */
    public function offsetGet($offset)
    {
        return isset($this->shared[$offset]) ? $this->shared[$offset] : null;
    }

    /**
     * ArrayAccess offsetSet
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     */
    public function offsetSet($offset, $value)
    {
        $this->shared[$offset] = $value;
    }

    /**
     * ArrayAccess offsetUnset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset($offset)
    {
        unset($this->shared[$offset]);
    }

    /**
     * Get the logger instance set on this object
     *
     * @return LoggerInterface The logger for use in this Auth object, or null if not set.
     */
    public function getLogger()
    {
        return $this->logger; /* Defined as part of the LoggerAwareInterface */
    }
}
