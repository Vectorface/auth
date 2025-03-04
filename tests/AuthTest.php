<?php

namespace Vectorface\Tests\Auth;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vectorface\Auth\Auth;
use Vectorface\Auth\Authentication\Plugin\FixedResult as FixedAuthenticationResult;
use Vectorface\Auth\Authorization\Plugin\FixedResult as FixedAuthorizationResult;
use Vectorface\Tests\Auth\Plugin\CanWhenAuthenticated;

class AuthTest extends TestCase
{
    #[Test]
    public function synopsis()
    {
        /* The auth class is meant to perform both authentication and authorization using related plugins */
        $auth = new Auth(
            new CanWhenAuthenticated(),
            new FixedAuthorizationResult(),
            new FixedAuthenticationResult(),
        );

        /* can't do anything with a failed authorization */
        $this->assertFalse($auth->can("do anything"));
        $this->assertFalse($auth->authenticate());
        $this->assertFalse($auth->can("do anything"));

        /* can do anything after authentication */
        $auth(FixedAuthenticationResult::class)->result(true);
        $this->assertTrue($auth->authenticate());
        $this->assertTrue($auth->can("do anything"));
        $this->assertTrue($auth->deauthenticate());
        $this->assertFalse($auth->can("do anything"));
    }
}