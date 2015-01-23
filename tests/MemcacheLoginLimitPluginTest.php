<?php

namespace Vectorface\Tests\Auth;

// hack to override the default "error_log" method so we don't print errors to
// the screen while testing
require_once __DIR__ . '/helpers/error_log.php';

use Vectorface\Tests\Cache\Helpers\Memcache;
use Vectorface\Tests\Cache\Helpers\FakeMemcache;
use Vectorface\Auth\Auth;
use Vectorface\Auth\AuthException;
use Vectorface\Auth\Plugin\MemcacheLoginLimitPlugin;
use Vectorface\Auth\Plugin\SuccessPlugin;
use Vectorface\Auth\Plugin\NullPlugin;
use \SplFixedArray;
use \Exception;

class MemcacheLoginLimitPluginTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        if (!class_exists('Memcache')) {
            class_alias('Vectorface\Tests\Cache\Helpers\Memcache', 'Memcache');
        }
    }

    public function testMemcacheLoginLimit()
    {
        $attempts = 5;

        $auth = new Auth();
        $fakemc = new FakeMemcache();
        $mclim = new MemcacheLoginLimitPlugin($fakemc, $attempts);
        $mclim->setRemoteAddr('localhost');
        $success = new SuccessPlugin();

        $auth->addPlugin($success);
        $auth->addPlugin($mclim);

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

        /* If memcache fails, this is now expected to succeed and emit a warning. */
        FakeMemcache::$broken = true;
        $this->assertTrue($auth->login('u', 'p'));
        FakeMemcache::$broken = false;
    }
}
