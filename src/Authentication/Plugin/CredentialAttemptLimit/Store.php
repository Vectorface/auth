<?php

namespace Vectorface\Auth\Authentication\Plugin\CredentialAttemptLimit;

use Vectorface\Auth\Authentication\Credential;

interface Store
{
    /**
     * Increments the failed attempt count for the given credential
     */
    public function increment(Credential $credential, int $timeout): int;

    /**
     * Resets teh attempt count for the given credential, typically on successful authentication
     */
    public function reset(Credential $credential): void;
}