<?php

namespace Vectorface\Tests\Auth\Plugin;

use Exception as Exception;
use InvalidArgumentException;
use Vectorface\Auth\Authentication\Credential;
use Vectorface\Auth\Authentication\PluginInterface as AuthenticationPluginInterface;
use Vectorface\Auth\Authorization\PluginInterface as AuthorizationPluginInterface;
class ThrowException implements AuthenticationPluginInterface, AuthorizationPluginInterface
{
    /**
     * @param class-string<Exception> $exceptionClass
     */
    public function __construct(
        private string $exceptionClass,
        private array $exceptionArgs = [],
    ) {
        if (! is_a($this->exceptionClass, Exception::class, true)) {
            throw new InvalidArgumentException('The exception class must an Exception or a subclass thereof');
        }
    }

    /**
     * @inheritDoc
     */
    public function authenticate(callable $next, Credential ...$credentials): ?bool
    {
        throw new ($this->exceptionClass)(...$this->exceptionArgs);
    }

    /**
     * @inheritDoc
     */
    public function deauthenticate(callable $next): ?bool
    {
        throw new ($this->exceptionClass)(...$this->exceptionArgs);
    }

    public function can(callable $next, mixed $resource, mixed $subject = null): ?bool
    {
        throw new ($this->exceptionClass)(...$this->exceptionArgs);
    }
}