<?php

namespace Vectorface\Auth\Authorization;

interface PluginInterface
{
    /**
     * Determine if a subject can perform an action
     *
     * @return bool true when the subject can perform the action
     */
    public function can(callable $next, mixed $action, mixed $subject = null): bool;
}