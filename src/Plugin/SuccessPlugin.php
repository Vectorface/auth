<?php

namespace Vectorface\Auth\Plugin;

use Vectorface\Auth\Auth;

/**
 * An auth plugin that always succeeds. Useful in development.
 */
class SuccessPlugin extends BaseAuthPlugin
{
    /**
     * Auth plugin hook to be fired on login.
     *
     * @param string $username
     * @param string $password
     * @return int
     */
    public function login($username, $password)
    {
        return Auth::RESULT_SUCCESS;
    }

    /**
     * Auth plugin hook to be fired on auth verification.
     *
     * @return int
     */
    public function verify()
    {
        return Auth::RESULT_SUCCESS;
    }

    /**
     * Auth plugin hook to be fired on logout.
     *
     * @return int
     */
    public function logout()
    {
        return Auth::RESULT_SUCCESS;
    }
}
