<?php

namespace Vectorface\Tests\Auth;

use Vectorface\Auth\Auth;
use Vectorface\Auth\Plugin\Limit\CookieLoginLimitPlugin;
use Vectorface\Auth\Plugin\SuccessPlugin;

abstract class LoginLimitPluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Subclass should create its auth object in this method.
     */
    abstract protected function getAuth($attempts);

    /**
     * @dataProvider loginLimitDataProvider
     */
    public function testLoginLimit($attempts)
    {
        $auth = $this->getAuth($attempts);

        $this->assertEquals(0, $auth->getLoginAttempts());
        $this->assertEquals($attempts, $auth->getMaxLoginAttempts());

        for ($i = 0; $i < $attempts; $i++) {
            $this->assertTrue($auth->login('u', 'p'), "Login attempt $i failed -> {$auth->getLoginAttempts()}");
        }

        $this->assertFalse($auth->login('u', 'p'));

        /* Rewind a little and try again. */
        $auth->decrementAttempts();
        $auth->decrementAttempts();

        $this->assertTrue($auth->login('u', 'p'));
        $this->assertFalse($auth->login('u', 'p'));
    }

    public function loginLimitDataProvider()
    {
        return [
            [5] // Try with 5 attempts.
        ];
    }
}
