<?php

namespace Vectorface\Auth\Authentication\Credential;

use Vectorface\Auth\Authentication\Credential;

class Password implements Credential, Comparable
{
    use StringTrait;

    public function compare(string $value): bool
    {
        return $this->value === $value || password_verify($this->value, $value);
    }
}