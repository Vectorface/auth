<?php

namespace Vectorface\Auth\Authentication;

interface Credential
{
    public static function type(): string;
    public function value(): mixed;
}