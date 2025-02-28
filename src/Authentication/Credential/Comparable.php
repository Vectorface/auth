<?php

namespace Vectorface\Auth\Authentication\Credential;

interface Comparable
{
    public function compare(string $value): bool;
}