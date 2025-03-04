<?php

namespace Vectorface\Auth\Common;

use Psr\Log\LogLevel;

trait StackExecutorTrait
{
    /**
     * Executes a stack of callables in a middleware-compatible pattern
     *
     * The stack executor will return null in cases where the stack didn't provide an answer. It is up to the caller to
     * transform the null into a meaningful value. (e.g. bool for authentication, etc.)
     */
    private function executeStack(string $name, array $args, callable ...$callables): mixed
    {
        $logger = $this->logger ?? null;
        reset($callables);
        $next = function(callable $next, ...$args) use(&$callables, $name, $logger): mixed {
            $callable = current($callables);
            if ($callable === false) {
                return null;
            }
            $identifier = key($callables);

            next($callables);
            $result = $callable(fn(...$args) => $next($next, ...$args), ...$args);
            $logger?->debug(static::class . ": {$name}::{$identifier} result", ['result' => $result]);
            return $result;
        };

        $result = null;
        try {
            $result = $next($next, ...$args);
        } catch (\Exception $e) {
            $logger?->log($this->logLevel ?? LogLevel::WARNING, static::class . ": Exception occurred during {$name}", ['exception' => $e->getMessage()]);
            throw $e;
        }
        return $result;
    }
}