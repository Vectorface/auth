<?php

namespace Vectorface\Auth\Authentication\Plugin\CredentialAttemptLimit;

use Vectorface\Auth\Authentication\Credential;

class EphermeralStore implements Store
{
    private array $store = [];

    public function increment(Credential $credential, int $timeout): int
    {
        $key = $this->key($credential);
        $timeout = time() + $timeout; // Now an absolute time
        [$attempts, $expiry] = $this->store[$key] ?? [0, $timeout];
        $attempts = ($expiry < time()) ? 1 : $attempts + 1;
        $this->store[$key] = [$attempts, $timeout];
        return $attempts;
    }

    public function reset(Credential $credential): void
    {
        unset($this->store[$this->key($credential)]);
    }

    private function key(Credential $credential): string
    {
        return "{$credential->type()}:{$credential->value()}";
    }
}