<?php

namespace Vectorface\Auth\Plugin\Limit;

/**
 * Generic interface to be implemented by Login (rate) limiting plugins.
 */
interface LoginLimitPluginInterface
{
    /**
     * Decrement the number of attempts.
     *
     * This is intended for application logic when a login attempt can be forgiven.
     */
    public function decrementAttempts();

    /**
     * Get the number of login attempts performed so far.
     *
     * Note: The number of attempts does not necessarily imply success or
     * failure, though if a logged-in user has 3 login attempts, that usually
     * means 2 failed 1 successful. If the user has not authenticated, 3
     * attempts means 3 failed attempts.
     *
     * @return int The number of login attempts performed so far, according to this plugin.
     */
    public function getLoginAttempts();

    /**
     * Get the maximum number of allowed login attempts for this plugin.
     *
     * @return int
     */
    public function getMaxLoginAttempts();
}
