<?php

namespace Vectorface\Auth\Authorization;

interface PluginInterface
{
    /**
     * Determine if a subject can access a resource
     *
     * @return bool true when the subject can access the resource
     */
    public function can(callable $next, mixed $resource, mixed $subject = null): bool;
}