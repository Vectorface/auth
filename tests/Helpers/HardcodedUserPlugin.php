<?php

namespace Vectorface\Tests\Auth\Helpers;

use Vectorface\Auth\Authenticator;
use Vectorface\Auth\Plugin\BasePlugin;

/**
 * An auth plugin that hard-codes username/password foo/bar.
 */
class HardcodedUserPlugin extends BasePlugin
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
        return ($username === 'foo' && $password === 'bar') ? Authenticator::RESULT_SUCCESS : Authenticator::RESULT_FAILURE;
    }
}
