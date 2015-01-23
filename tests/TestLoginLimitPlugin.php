<?php

namespace Vectorface\Tests\Auth;

use Vectorface\Auth\Auth;
use Vectorface\Auth\Plugin\Limit\CookieLoginLimitPlugin;

/**
 * An auth plugin for testing.
 */
class TestLoginLimitPlugin extends CookieLoginLimitPlugin
{
    public $result = Auth::RESULT_SUCCESS;

    public function login($username, $password) { return $this->result; }
    public function verify() { return $this->result; }
    public function logout() { return $this->result; }
}
