<?php

namespace Vectorface\Tests\Auth;

use Vectorface\Auth\Auth;
use Vectorface\Auth\Plugin\Limit\CookieLoginLimitPlugin;
use Vectorface\Auth\Plugin\SuccessPlugin;

class CookieLoginLimitPluginTest extends LoginLimitPluginTest
{
    public static function setUpBeforeClass() : void
    {
        // Define a functions to override headers_sent and setcookie with empty stubs.
        eval('namespace Vectorface\Auth\Plugin\Limit { function headers_sent() {} function setcookie() {} }');
        parent::setUpBeforeClass();
    }

    public function getAuth($attempts)
    {
        $auth = new Auth();
        $lim = new CookieLoginLimitPlugin($attempts);
        $success = new SuccessPlugin();

        $auth->addPlugin($success);
        $auth->addPlugin($lim);

        return $auth;
    }
}
