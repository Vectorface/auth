<?php

namespace Vectorface\Tests\Auth;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Vectorface\Auth\Authentication\Authenticator;
use Vectorface\Auth\Authentication\Credential\Password;
use Vectorface\Auth\Authentication\Credential\UserIdentifier;
use Vectorface\Auth\Authentication\Plugin\CredentialAttemptLimit;
use Vectorface\Auth\Authentication\Plugin\CredentialAttemptLimit\EphermeralStore;
use Vectorface\Auth\Authentication\Plugin;

class AuthenticatorTest extends TestCase
{
    #[Test]
    #[DataProvider('attemptProvider')]
    public function synopsis(array $attempts, array $results)
    {
        /* An authenticator will execute its plugins in order. Here, an attempt limiter and a user/pass checker */
        $authenticator = new Authenticator(
            /* Allow 3 authentication attempts for a given user identifier */
            new CredentialAttemptLimit(UserIdentifier::type(), new EphermeralStore(), 3, 60),
            /* Authenticate against  */
            new Plugin\Map(
                ['bob' => password_hash('53CUR3', PASSWORD_DEFAULT)],
                UserIdentifier::type(),
                Password::type(),
            ),
        );

        foreach ($attempts as $attempt => [$username, $password]) {
            $result = $authenticator->authenticate(new UserIdentifier($username), new Password($password));
            $this->assertEquals($results[$attempt], $result);
        }

        /* If we logged in, log back out again */
        if ($result) {
            $this->assertTrue($authenticator->deauthenticate());
        }
    }

    public static function attemptProvider(): array
    {
        return [
            '0 failures, 1 success' => [
                [['bob', '53CUR3']],
                [true],
            ],
            '1 failure, 1 success' => [
                [['bob', 'secure'], ['bob', '53CUR3']],
                [false, true],
            ],
            '2 failures, 1 success' => [
                [['bob', 'secure'], ['bob', 'eruces'], ['bob', '53CUR3']],
                [false, false, true],
            ],
            '3 failures, 1 failure with correct password' => [ // Fails because it hits the attempt limiter
                [['bob', 'guess'], ['bob', 'second'], ['bob', 'third'], ['bob', '53CUR3']],
                [false, false, false, false],
            ],
        ];
    }

    public function testFixedResults()
    {
        /* Start with a fixed fail */
        $authenticator = new Authenticator(Plugin\FixedResult::class);
        $this->assertFalse($authenticator->authenticate());

        /* Change the fixed result to a success */
        $authenticator(Plugin\FixedResult::class)->result(true);
        $this->assertTrue($authenticator->authenticate());
    }

    public function testInvalidPluginClass()
    {
        $this->expectException(\InvalidArgumentException::class, 'Authenticator should only accept PluginInterface classes');
        new Authenticator('not a valid classname');
    }

    public function testGetPluginFail()
    {
        $authenticator = new Authenticator();
        $this->assertNull($authenticator(Plugin\FixedResult::class));
    }
}