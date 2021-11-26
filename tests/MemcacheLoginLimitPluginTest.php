<?php

namespace Vectorface\Tests\Auth;

use Vectorface\Tests\Auth\Helpers\FakeMemcache;
use Vectorface\Auth\Auth;
use Vectorface\Auth\Plugin\Limit\MemcacheLoginLimitPlugin;
use Vectorface\Auth\Plugin\SuccessPlugin;
use Vectorface\Tests\Auth\Helpers\Memcache;

class MemcacheLoginLimitPluginTest extends LoginLimitPluginTest
{
    protected $fakeMemcache;

    public static function setUpBeforeClass() : void
    {
        if (!class_exists('Memcache')) {
            /** @noinspection PhpIgnoredClassAliasDeclaration */
            class_alias(Memcache::class, 'Memcache');
        }
        parent::setUpBeforeClass();
    }

    protected function getAuth($attempts)
    {
        $auth = new Auth();
        $fakemc = new FakeMemcache();
        $this->fakeMemcache = $fakemc;
        $fakemc->flush();
        $mclim = new MemcacheLoginLimitPlugin($fakemc, $attempts);
        $mclim->setRemoteAddr('localhost');
        $success = new SuccessPlugin();

        $auth->addPlugin($success);
        $auth->addPlugin($mclim);

        return $auth;
    }

    /**
     * @throws \Vectorface\Auth\AuthException
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testBrokenMemcache()
    {
        $auth = $this->getAuth(5);

        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($auth->login('u', 'p'), "Login attempt $i failed -> {$auth->getLoginAttempts()}");
        }

        /* If memcache fails, this is now expected to succeed and emit a warning. */
        $this->fakeMemcache->broken = true;
        $this->assertTrue($auth->login('u', 'p'));
        $this->fakeMemcache->broken = false;
    }
}
