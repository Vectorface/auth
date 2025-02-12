<?php

namespace Vectorface\Auth\Plugin;

use Vectorface\Auth\Authenticator;

/**
 * An auth plugin that always succeeds. Useful in development.
 */
class SuccessPlugin extends BasePlugin
{
    /**
     * Authenticator plugin hook to be fired on login.
     *
     * @param string $username
     * @param string $password
     * @return int
     */
    public function login($username, $password)
    {
        return Authenticator::RESULT_SUCCESS;
    }

    /**
     * Authenticator plugin hook to be fired on auth verification.
     *
     * @return int
     */
    public function verify()
    {
        return Authenticator::RESULT_SUCCESS;
    }

    /**
     * Authenticator plugin hook to be fired on logout.
     *
     * @return int
     */
    public function logout()
    {
        return Authenticator::RESULT_SUCCESS;
    }
}
