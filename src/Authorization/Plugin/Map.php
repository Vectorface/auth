<?php

namespace Vectorface\Auth\Authorization\Plugin;

use ArrayAccess;
use Vectorface\Auth\Authorization\PluginInterface;

class Map implements PluginInterface
{
    /**
     * A fixed map of authorized actions; Subject is ignored
     */
    public function __construct(
        private array|ArrayAccess $map,
    ) {}

    /**
     * Determine if *subject* can *action*
     */
    public function can(callable $next, mixed $resource, mixed $subject = null): bool
    {
        try {
            $isset = isset($this->map[$resource]);
        } catch (\TypeError) {
            $isset = false; // $resource is not a valid array, so is *not* in the WeakMap!
        }
        return $isset ? (bool)$this->map[$resource] : $next($resource, $subject);
    }
}