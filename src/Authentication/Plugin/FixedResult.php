<?php

namespace Vectorface\Auth\Authentication\Plugin;

use Vectorface\Auth\Authentication\Credential;
use Vectorface\Auth\Authentication\PluginInterface;

/**
 * Produce a fixed authentication result
 *
 * This can be used to set a default (true/false) or to force a specific result for testing.
 */
class FixedResult implements PluginInterface
{
    public function __construct(
        private bool $result = false,
        private readonly bool $callStack = false
    ) {}

    /**
     * Set the fixed result
     */
    public function result(bool $result): static
    {
        $this->result = $result;
        return $this;
    }

    public function authenticate(callable $next, Credential ...$credentials): bool
    {
        if ($this->callStack) {
            $next(...$credentials);
        }
        return $this->result;
    }

    public function deauthenticate(callable $next): bool
    {
        if ($this->callStack) {
            $next();
        }
        return $this->result;
    }
}