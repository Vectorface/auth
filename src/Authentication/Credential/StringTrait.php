<?php

namespace Vectorface\Auth\Authentication\Credential;

use ReflectionClass;

trait StringTrait
{
    use SimpleTypeTrait;

    public function __construct(
        private readonly string $value
    ) {}

    public function value(): string
    {
        return $this->value;
    }
}