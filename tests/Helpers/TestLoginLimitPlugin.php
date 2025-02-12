<?php

namespace Vectorface\Tests\Auth\Helpers;

use Vectorface\Auth\Authenticator;
use Vectorface\Auth\Plugin\Limit\CookieLoginLimitPlugin;

/**
 * An auth plugin for testing.
 */
class TestLoginLimitPlugin extends CookieLoginLimitPlugin
{
    public $result = Authenticator::RESULT_SUCCESS;

    public function login($username, $password)
    {
        return $this->result;
    }
    public function verify()
    {
        return $this->result;
    }
    public function logout()
    {
        return $this->result;
    }
}
