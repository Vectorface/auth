<?php

namespace Vectorface\Tests\Auth;

use Vectorface\Auth\Auth;
use Vectorface\Auth\Plugin\Limit\HybridLoginLimitPlugin;
use Vectorface\Auth\Plugin\Limit\CookieLoginLimitPlugin;
use Vectorface\Auth\Plugin\SuccessPlugin;

class HybridLoginLimitPluginTest extends LoginLimitPluginTest
{
    public function getAuth($attempts)
    {
        $auth = new Auth();
        $sublim1 = new CookieLoginLimitPlugin($attempts);
        $sublim2 = new CookieLoginLimitPlugin($attempts);
        $lim = new HybridLoginLimitPlugin([$sublim1, $sublim2]);
        $success = new SuccessPlugin();

        $auth->addPlugin($success);
        $auth->addPlugin($lim);

        return $auth;
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArg()
    {
        new HybridLoginLimitPlugin([123]);
    }

    /**
     * A normal login limit plugin shouldn't return success, but it should work anyway.
     */
    public function testWeirdSuccess()
    {
        $test = new TestLoginLimitPlugin();
        $test->result = Auth::RESULT_SUCCESS;
        $lim = new HybridLoginLimitPlugin([$test]);
        $this->assertEquals(Auth::RESULT_SUCCESS, $lim->login('u', 'p'));
    }
}
