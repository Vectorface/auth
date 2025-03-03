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
     * @param string $keyCredentialType
     * @param string $valueCredentialType
     */
    public function __construct(
        private readonly array $map,
        private readonly string $keyCredentialType,
        private readonly string $valueCredentialType,
    ) {}

    public function authenticate(callable $next, Credential ...$credentials): bool
    {
        /* 1. Find the credentials */
        $key = null;
        $value = null;
        foreach ($credentials as $credential) {
            if ($credential::type() === $this->keyCredentialType) {
                $key = $credential->value();
            } elseif ($credential::type() === $this->valueCredentialType) {
                $value = $credential;
            }

            if (isset($key, $value)) {
                break;
            }
        }

        /* 2. Make sure we have the required credentials and the value exists in the map */
        if (!isset($key, $value, $this->map[$key])) {
            return false;
        }

        /*  3. Check if the credentials match the value in the map; non-existent or non-matching means false */
        if ($value instanceof Credential\Comparable) {
            if (!$value->compare($this->map[$key])) {
                return false;
            }
        } elseif ($this->map[$key] !== $value) {
            return false;
        }

        /* 4. Authentication succeeded: Continue down the stack and return the stack value or true if we're at the end of the stack */
        return $next(...$credentials) ?? true;
    }

    public function deauthenticate(callable $next): bool
    {
        return $next() ?? true;
    }
}