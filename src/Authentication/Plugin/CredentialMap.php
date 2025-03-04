<?php

namespace Vectorface\Auth\Authentication\Plugin;

use Vectorface\Auth\Authentication\Credential;
use Vectorface\Auth\Authentication\PluginInterface;

/**
 * A plugin that maps credentials to values and returns true if the value matches.
 *
 * In plain English: This is meant to map a fixed list of usernames and passwords or tokens, mostly for testing
 */
class CredentialMap implements PluginInterface
{
    /**
     * @param array<string, string> $map A map of keys to expected credential values
     */
    public function __construct(
        private readonly array $map,
        private readonly string $keyCredentialType = 'useridentifier' /* UserIdentifier::type() */,
        private readonly string $valueCredentialType = 'password' /* Password::type() */,
    ) {}

    public function authenticate(callable $next, Credential ...$credentials): ?bool
    {
        /* 1. Find the credentials */
        $key = null;
        $valueCredential = null;
        foreach ($credentials as $credential) {
            if ($credential::type() === $this->keyCredentialType) {
                $key = $credential->value();
            } elseif ($credential::type() === $this->valueCredentialType) {
                $valueCredential = $credential;
            }

            if (isset($key, $valueCredential)) {
                break;
            }
        }

        /* 2. Make sure we have the required credentials and the value exists in the map */
        if (!isset($key, $valueCredential) || !isset($this->map[$key])) {
            return $next(...$credentials); // No information. Continue down the stack.
        }

        /*  3. Check if the credentials match the value in the map; non-existent or non-matching means false */
        if ($valueCredential instanceof Credential\Comparable) {
            if (!$valueCredential->compare($this->map[$key])) {
                return false;
            }
        } elseif ($this->map[$key] !== $valueCredential->value()) {
            return false;
        }

        /* 4. Authentication succeeded: Continue down the stack and return the stack value or true if we're at the end of the stack */
        return $next(...$credentials) ?? true;
    }

    public function deauthenticate(callable $next): ?bool
    {
        return $next() ?? true;
    }
}