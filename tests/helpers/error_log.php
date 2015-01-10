<?php

namespace Vectorface\Auth;

/**
 * Fake error logging to prevent actual logging output during tests.
 *
 * @param string $string ignored.
 */
function error_log($string)
{
    return is_scalar($string);
}
