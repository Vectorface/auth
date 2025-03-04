<?php

namespace Vectorface\Tests\Auth;

use Exception;
use Monolog\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Vectorface\Auth\Authentication\Authenticator;
use Vectorface\Auth\Authentication\Credential;
use Vectorface\Auth\Authentication\Plugin\CredentialAttemptLimit;
use Vectorface\Auth\Authentication\Plugin\CredentialAttemptLimit\EphermeralStore;
use Vectorface\Auth\Authentication\Plugin;

class AuthenticatorTest extends TestCase
{
    /**
     * @param array $attempts Represents a series of authentication attempts, each with a user identifier, password, and token
     * @param array $results Represents the expected results of each authentication attempt
     * @throws Exception
     */
    #[Test]
    #[DataProvider('attemptProvider')]
    public function synopsis(array $attempts, array $results)
    {
        /* An authenticator will execute its plugins in order. Here, an attempt limiter and a user/pass checker */
        $authenticator = new Authenticator(
            /* Allow 3 authentication attempts for a given user identifier */
            new CredentialAttemptLimit(Credential\UserIdentifier::type(), new EphermeralStore(), 3, 60),
            /* Authenticate against a password */
            new Plugin\CredentialMap([
                'bob' => password_hash('53CUR3', PASSWORD_BCRYPT, ['cost' => 4 /* make tests run quickly */]),
                'carol' => 'Pl@1N73x7', /* plaintext password */
            ]),
            /* Authenticate against a session token */
            token: new Plugin\CredentialMap(['carol' => 'session-token-abc123'], valueCredentialType: Credential\Token::type()),
        );

        foreach ($attempts as $attempt => $credentials) {
            $credentials = array_filter([
                new Credential\UserIdentifier($credentials[0]),
                isset($credentials[1]) ? new Credential\Password($credentials[1]) : null,
                isset($credentials[2]) ? new Credential\Token($credentials[2]) : null,
            ]);
            $result = $authenticator->authenticate(...$credentials);
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
            'unknown username' => [
                [['alice', 'P@55w0rd']],
                [false],
            ],
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
            'plaintext password' => [
                [['carol', 'plaintext'], ['carol', 'Pl@1N73x7']],
                [false, true],
            ],
            'session token success' => [
                [['carol', null, 'session-token-abc123']],
                [true],
            ],
            'session token failure' => [
                [['carol', null, 'session-token-xyz789']],
                [false],
            ]
        ];
    }

    public function testFixedResults()
    {
        /* Start with a fixed fail */
        $authenticator = new Authenticator(
            result: new Plugin\FixedResult(false, true),
            stack: new Plugin\FixedResult(null),
        );
        $this->assertFalse($authenticator->authenticate());
        $this->assertFalse($authenticator->deauthenticate());


        /* Change the fixed result to a success */
        $authenticator("result")->result(true);
        $this->assertTrue($authenticator->authenticate());
        $this->assertTrue($authenticator->deauthenticate());

        /* ... But if the rest of the stack fails, this will too */
        $authenticator("stack")->result(false);
        $this->assertFalse($authenticator->authenticate());
        $this->assertFalse($authenticator->deauthenticate());

    }

    public function testInvalidPlugin()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Authenticator('not a valid plugin class');
    }

    public function testPluginCollision()
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Authenticator())
            ->register(new Plugin\FixedResult(), 'default')
            ->register(Plugin\FixedResult::class, 'default');
    }

    public function testGetPlugin()
    {
        $passwordPlugin = new Plugin\CredentialMap(['bob' => password_hash('53CUR3', PASSWORD_BCRYPT, ['cost' => 4 /* make tests run quickly */])]);
        $plaintextPlugin = new Plugin\CredentialMap(['alice' => 'P@55w0rd']);

        $authenticator = new Authenticator(
            $passwordPlugin,
            plaintext: $plaintextPlugin,
        );

        /* Plugins can be referenced by class */
        $this->assertSame($passwordPlugin, $authenticator(Plugin\CredentialMap::class));
        /* Plugins can also be referenced by identifier. Useful if two of the same are used. */
        $this->assertSame($plaintextPlugin, $authenticator('plaintext'));
        /* Will return nothing if it doesn't have the given plugin class/identifier registered */
        $this->assertNull($authenticator(Plugin\FixedResult::class));
    }
}