<?php

namespace Vectorface\Auth;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Vectorface\Auth\Plugin\AuthPluginInterface;
use Exception;

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
    private $plugins = [];

    /**
     * Shared values, accessible using the ArrayAccess interface.
     *
     * @var mixed[]
     */
    private $shared = [];

    /**
     * Add a plugin to the Auth module.
     *
     * @param string|AuthPluginInterface The name of a plugin class to be registered, or a configured instance of a
     *                                   security plugin.
     * @return bool True if the plugin was added successfully.
     */
    public function addPlugin($plugin)
    {
        if (!($plugin instanceof AuthPluginInterface)) {
            if (!is_string($plugin)) {
                return false;
            }
            if (!in_array(AuthPluginInterface::class, class_implements($plugin, false))) {
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
     * @throws AuthException
     */
    public function login($username, $password)
    {
        return $this->action(self::ACTION_LOGIN, [$username, $password]);
    }

    /**
     * Attempt to log the user out.
     *
     * @throws AuthException
     */
    public function logout()
    {
        return $this->action(self::ACTION_LOGOUT);
    }

    /**
     * Verify a user's saved data - check if the user is logged in.
     *
     * @return bool True if the user's session was verified successfully.
     * @throws AuthException
     */
    public function verify()
    {
        return $this->action(self::ACTION_VERIFY);
    }

    /**
     * Call an action on a plugin, with particular arguments.
     *
     * @param AuthPluginInterface $plugin The plugin on which to run the action.
     * @param string $action The plugin action, login, logout, or verify.
     * @param mixed[] $arguments A list of arguments to pass to the action.
     * @return int The result returned by the Auth plugin.
     * @throws AuthException
     */
    private function callPlugin(AuthPluginInterface $plugin, $action, $arguments)
    {
        /* This is a bit of defensive programming; It is not possible to hit this code. */
        // @codeCoverageIgnoreStart
        if (!in_array($action, array(self::ACTION_LOGIN, self::ACTION_LOGOUT, self::ACTION_VERIFY))) {
            throw new AuthException("Attempted to perform unknown action: $action");
        }
        // @codeCoverageIgnoreEnd

        $result = call_user_func_array([$plugin, $action], $arguments);

        /* Expected results are an integer or null; Anything else is incorrect. */
        if (!(is_int($result) || $result === null)) {
            throw new AuthException(sprintf(
                "Unknown %s result in %s plugin: %s",
                $action,
                get_class($plugin),
                print_r($result, true)
            ));
        }

        return $result;
    }

    /**
     * Perform a generalized action: login, logout, or verify: Run said function on each plugin in order verifying
     * result along the way.
     *
     * @param string $action The action to be taken. One of login, logout, or verify.
     * @param mixed[] $arguments A list of arguments to pass to the action.
     * @return bool True if the action was successful, false otherwise.
     * @throws AuthException
     */
    private function action($action, $arguments = [])
    {
        $success = false;

        foreach ($this->plugins as $plugin) {
            try {
                $result = $this->callPlugin($plugin, $action, $arguments);
            } catch (AuthException $e) {
                throw $e;
            } catch (Exception $e) {
                $this->logException($e, "Fatal %s error in %s plugin", $action, get_class($plugin));
                return false;
            }

            if ($result === self::RESULT_SUCCESS /* 0 */) {
                $success = true;
            } elseif ($result !== self::RESULT_NOOP) {
                /* If not noop or success, possible options are hard fail (<=-1), or hard success (>=1) */
                return ($result >= self::RESULT_FORCE);
            } /* else result is NOOP. Do nothing. Result not defined for plugin. */
        }
        return $success;
    }

    /**
     * Passthrough function call for plugins.
     *
     * @param string $method The name of the method to be called.
     * @param array $args An array of arguments to be passed to the method.
     * @return mixed Returns whatever the passthrough function returns, or null or error or missing function.
     * @throws AuthException
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function __call($method, $args = [])
    {
        foreach ($this->plugins as $plugin) {
            if (is_callable([$plugin, $method])) {
                try {
                    return call_user_func_array([$plugin, $method], $args);
                } catch (AuthException $e) {
                    throw $e;
                } catch (Exception $e) {
                    return $this->logException($e, "Exception caught calling %s->%s", get_class($plugin), $method);
                }
            }
        }

        if ($this->logger) {
            $this->logger->warning(__CLASS__ . ": $method not implemented by any loaded plugin");
        }
        return null;
    }

    /**
     * ArrayAccess offsetExists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->shared[$offset]);
    }

    /**
     * ArrayAccess offsetGet
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->shared[$offset] ?? null;
    }

    /**
     * ArrayAccess offsetSet
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->shared[$offset] = $value;
    }

    /**
     * ArrayAccess offsetUnset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @inheritDoc
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

    /**
     * Log a message and exception in a semi-consistent form.
     *
     * Logs the message, and appends exception message and location.
     *
     * @param Exception $exc The exception to log.
     * @param string $message The exception message in printf style.
     * @param string ... Any number of string parameters corresponding to %s placeholders in the message string.
     * @noinspection PhpUnusedLocalVariableInspection
     */
    private function logException(Exception $exc, $message /*, ... */)
    {
        if ($this->logger) {
            $args = array_slice(func_get_args(), 2);
            $message .= ": %s (%s@%s)"; /* Append exception info to log string. */
            $args = array_merge($args, [$exc->getMessage(), $exc->getFile(), $exc->getLine()]);
            $this->logger->warning(call_user_func_array('sprintf', $args));
        }
    }
}
