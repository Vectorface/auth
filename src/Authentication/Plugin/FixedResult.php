<?php

namespace Vectorface\Auth\Authentication\Plugin;

use Vectorface\Auth\Authentication\Credential;
use Vectorface\Auth\Authentication\PluginInterface;

class FixedResult implements PluginInterface
{
    public function __construct(
        private readonly bool $result,
        private readonly bool $callStack = false
    ) {}

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