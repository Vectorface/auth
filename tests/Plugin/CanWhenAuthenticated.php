<?php

namespace Vectorface\Tests\Auth\Plugin;

use Vectorface\Auth\Authentication\Credential;
use Vectorface\Auth\Authentication\PluginInterface as AuthenticationPluginInterface;
use Vectorface\Auth\Authorization\PluginInterface as AuthorizationPluginInterface;

/**
 * Demonstrates linked authentication and authorization: only allow access to resources when authentication succeeds
 */
class CanWhenAuthenticated implements AuthenticationPluginInterface, AuthorizationPluginInterface
{
    private bool $authenticated = false;

    public function authenticate(callable $next, Credential ...$credentials): ?bool
    {
        $result = $next(...$credentials);
        $this->authenticated = $result === true;
        return $result;
    }

    public function deauthenticate(callable $next): ?bool
    {
        $wasAuthenticated = $this->authenticated;
        $this->authenticated = false;
        return $wasAuthenticated;
    }
    public function can(callable $next, mixed $resource, mixed $subject = null): ?bool
    {
        return $this->authenticated ? ($next($resource, $subject) ?? true) : false;
    }
}