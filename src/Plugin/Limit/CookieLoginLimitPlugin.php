<?php

namespace Vectorface\Auth\Plugin\Limit;

use Vectorface\Auth\Auth;
use Vectorface\Auth\Plugin\BaseAuthPlugin;

/**
 * Limit the number of logins allowed per browser. This is to be used alongside the MemcacheLoginLimitPlugin.
 *
 * This is not intended to be a security measure, but more as a guard against a single person locking out an entire office that's behind one NAT.
 */
class CookieLoginLimitPlugin extends BaseAuthPlugin implements LoginLimitPluginInterface
{
    /**
     * Name of the cookie.
     */
    const COOKIE_NAME = "AuthCLLP";

    /**
     * Default maximum number of login attempts.
     */
    const MAX_ATTEMPTS = 3;

    /**
     * Default timeout in seconds.
     */
    const TIMEOUT = 900;

    /**
     * The maximum allowed number of login attempts within the timeout.
     *
     * @var int
     */
    protected $maxAttempts;

    /**
     * The amount of time that must pass after the maximum number of failed login attempts.
     *
     * @var int
     */
    protected $timeout;

    /**
     * The number of attempts so far.
     *
     * @var int
     */
    protected $attempts;

    /**
     * Create a new cookie-based login limiter.
     *
     * @param int $attempts The maximum number of login attempts to be allowed.
     * @param int $sec The amount of time (in seconds) to block login attempts after max attempts has been surpassed.
     */
    public function __construct($attempts = self::MAX_ATTEMPTS, $sec = self::TIMEOUT)
    {
        $this->maxAttempts = (int)$attempts;
        $this->timeout = (int)$sec;
    }

    /**
     * Decrement the number of attempts.
     *
     * This is intended for application logic when a login attempt can be forgiven.
     */
    public function decrementAttempts()
    {
        $this->setLoginAttempts(-1);
    }

    /**
     * Get the number of login attempts performed so far.
     *
     * Note: The number of attempts does not necessarily imply success or
     * failure, though if a logged-in user has 3 login attempts, that usually
     * means 2 failed 1 successful. If the user has not authenticated, 3
     * attempts means 3 failed attempts.
     *
     * @return int The number of login attempts for this user.
     */
    public function getLoginAttempts()
    {
        if (!isset($this->attempts)) {
            $this->attempts = isset($_COOKIE[self::COOKIE_NAME]) ? $_COOKIE[self::COOKIE_NAME] : 0;
        }
        return $this->attempts;
    }

    /**
     * Get the maximum number of allowed login attempts.
     *
     * Note: max attempts for a given IP isn't published.
     *
     * @return int
     */
    public function getMaxLoginAttempts()
    {
        return $this->maxAttempts;
    }

    /**
     * Used internally to set the number of login attempts
     *
     * @param int $diff The change to number of login attempts, usually positive or negative 1.
     * @return int[] The number of attempts for the given username and IP address.
     */
    protected function setLoginAttempts($diff)
    {
        $this->attempts = $this->getLoginAttempts() + $diff;
        $_COOKIE[self::COOKIE_NAME] = $this->attempts;
        if (!headers_sent()) {
            setcookie(self::COOKIE_NAME, $this->attempts, time() + $this->timeout);
        }
        return $this->attempts;
    }

    /**
     * Auth plugin hook to be fired on login.
     *
     * @param string $username
     * @param string $password
     * @return int
     */
    public function login($username, $password)
    {
        $attempts = $this->setLoginAttempts(1);

        if ($attempts > $this->maxAttempts) {
            return Auth::RESULT_FAILURE;
        }

        return Auth::RESULT_NOOP;
    }
}
