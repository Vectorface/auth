<?php

namespace Vectorface\Auth;

/**
 * Represents a base auth plugin.
 */
abstract class BaseAuthPlugin implements AuthPluginInterface
{
    /**
     * Calling Security Class
     *
     * @var Security
     */
    private $auth;

    /**
     * Store the Auth class into which this plugin will plug. Called by the Auth class on plugin addition.
     *
     * @param Auth $auth
     */
    public function setAuth(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Get the Auth class instance
     *
     * @return Auth
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
        return Auth::RESULT_NOOP;
    }

    /**
     * Attempt to log the user out of the system.
     *
     * @return int The logout result.
     */
    public function logout()
    {
        return Auth::RESULT_NOOP;
    }

    /**
     * Attempt to verify the user's login status.
     *
     * @return int The user's login status.
     */
    public function verify()
    {
        return Auth::RESULT_NOOP;
    }
}
