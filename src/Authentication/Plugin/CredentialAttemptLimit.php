<?php

namespace Vectorface\Auth\Authentication\Plugin;

use Vectorface\Auth\Authentication\Credential;
use Vectorface\Auth\Authentication\Plugin\CredentialAttemptLimit\Store;
use Vectorface\Auth\Authentication\PluginInterface;

class CredentialAttemptLimit implements PluginInterface
{
    public function __construct(
        private string $credentialType,
        private Store  $store,
        private int    $maxAttempts = 5,
        private int    $timeout = 300,
    )
    {
    }

    public function authenticate(callable $next, Credential ...$credentials): bool
    {
        /* Find the target credential then increment */
        foreach ($credentials as $credential) {
            if ($credential::type() !== $this->credentialType) {
                continue;
            }

            $attempts = $this->store->increment($credential, timeout: $this->timeout);

            if ($attempts > $this->maxAttempts) {
                return false;
            }
        }

        $result = $next(...$credentials);

        if ($result) {
            $this->store->reset($credential);
        }

        return $result;
    }

    public function deauthenticate(callable $next): bool
    {
        return $next();
    }
}
