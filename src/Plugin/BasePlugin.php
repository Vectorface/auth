<?php

namespace Vectorface\Auth\Plugin;

use Vectorface\Auth\Authenticator;

/**
 * Represents a base auth plugin.
 */
abstract class BasePlugin implements PluginInterface
{
    /**
     * Calling Security Class
     *
     * @var Authenticator
     */
    private $auth;

    /**
     * Store the Authenticator class into which this plugin will plug. Called by the Authenticator class on plugin addition.
     *
     * @param Authenticator $auth
     */
    public function setAuth(Authenticator $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Get the Authenticator class instance
     *
     * @return Authenticator
     */
    protected function getAuth()
    {
        return $this->auth;
    }

    /**
     * Attempt to log the user in to the system.
     *
     * @param string $username The unique identifier for the user.
     * @param string $password The user's password.
     * @return int The login result.
     */
    public function login($username, $password)
    {
        return Authenticator::RESULT_NOOP;
    }

    /**
     * Attempt to log the user out of the system.
     *
     * @return int The logout result.
     */
    public function logout()
    {
        return Authenticator::RESULT_NOOP;
    }

    /**
     * Attempt to verify the user's login status.
     *
     * @return int The user's login status.
     */
    public function verify()
    {
        return Authenticator::RESULT_NOOP;
    }
}
