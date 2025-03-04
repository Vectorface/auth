<?php

namespace Vectorface\Auth\Authorization\Plugin;

use Vectorface\Auth\Authorization\PluginInterface;

/**
 * Produce a fixed authorization result
 *
 * This can be used to set a default (true/false) or to force a specific result for testing.
 */
class FixedResult implements PluginInterface
{
    public function __construct(
        private ?bool         $result = null,
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

    public function can(callable $next, mixed $resource, mixed $subject = null): ?bool
    {
        $stack = null;
        if ($this->callStack) {
            $stack = $next($resource, $subject);
        }
        return ($stack === false) ? false : $this->result;
        //return $this->callStack ? ($next($resource, $subject) ?? $this->result) : $this->result;
    }
}