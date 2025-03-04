<?php

namespace Vectorface\Auth\Authentication;

use Vectorface\Auth\Authenticator;

/**
 * Interface which must be implemented by Authenticator plugins.
 */
interface PluginInterface
{
    /**
     * Attempt to authenticate the user
     *
     * @return ?bool true if the credentials could be authenticated, false for failure, null for no result
     */
    public function authenticate(callable $next, Credential ...$credentials): ?bool;

    /**
     * Log out, or clear credential storage, etc.
     */
    public function deauthenticate(callable $next): ?bool;
}
