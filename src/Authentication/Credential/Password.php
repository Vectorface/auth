<?php

namespace Vectorface\Auth\Authentication\Credential;

use Vectorface\Auth\Authentication\Credential;

class Password implements Credential, Comparable
{
    use StringTrait;

    public function compare(string $value): bool
    {
        $info = password_get_info($value);
        if (isset($info) && !empty($info['algo'])) {
            return password_verify($this->value, $value);
        }
        return $value === $this->value;
    }
}