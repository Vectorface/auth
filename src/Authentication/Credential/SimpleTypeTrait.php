<?php

namespace Vectorface\Auth\Authentication\Credential;

use ReflectionClass;

trait SimpleTypeTrait
{
    public static function type(): string
    {
        return strtolower((new ReflectionClass(static::class))->getShortName());
    }
}