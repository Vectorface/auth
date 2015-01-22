<?php

namespace Vectorface\Auth;

/**
 * Interface which must be implemented by Auth plugins.
 */
interface AuthPluginInterface
{
    /**
     * Tell this instance which Auth class instance is using this plugin. Called by the Auth class on plugin addition.
     *
     * @param Auth $auth
     */
    public function setAuth(Auth $auth);

    /**
     * Attempt to log the user in to the system.
     *
     * @param string $username The unique identifier for the user.
     * @param string $password The user's password.
     * @return int The login result.
     */
    public function login($username, $password);

    /**
     * Attempt to log the user out of the system.
     *
     * @return int The logout result.
     */
    public function logout();

    /**
     * Attempt to verify the user's login status.
     *
     * @return int The user's login status.
     */
    public function verify();
}
