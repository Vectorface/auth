<?php

namespace Vectorface\Tests\Auth;

use PHPUnit\Framework\TestCase;

abstract class LoginLimitPluginTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        // Clear our the cookie cache between tests
        $_COOKIE = [];
        parent::setUpBeforeClass();
    }

    /**
     * Subclass should create its auth object in this method.
     *
     * @param int $attempts
     */
    abstract protected function getAuth($attempts);

    /**
     * @dataProvider loginLimitDataProvider
     * @param int $attempts
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
