<?php

namespace Vectorface\Auth\Authorization\Plugin;

use Vectorface\Auth\Authorization\PluginInterface;

class MapPlugin implements PluginInterface
{
    /**
     * A fixed map of authorized actions; Subject is ignored
     */
    public function __construct(
        private array $map,
    ) {}

    public function can(callable $next, mixed $action, mixed $subject = null): bool
    {
        if (! is_scalar($action)) {
            return $next($subject, $action);
        }

        return $this->map[$action] ?? false;
    }
}