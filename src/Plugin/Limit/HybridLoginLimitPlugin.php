<?php

namespace Vectorface\Auth\Plugin\Limit;

use InvalidArgumentException;
use Vectorface\Auth\Auth;
use Vectorface\Auth\Plugin\BaseAuthPlugin;

/**
 * Limit the number of logins allowed per browser. This is to be used alongside the MemcacheLoginLimitPlugin.
 *
 * This is not intended to be a security measure, but more as a guard against a single person locking out an entire office that's behind one NAT.
 */
class HybridLoginLimitPlugin extends BaseAuthPlugin implements LoginLimitPluginInterface
{
    /**
     * The login limit plugins to "combine".
     *
     * @var LoginLimitPluginInterface[]
     */
    protected $limiters = [];

    /**
     * Create a new hybrid login limiter.
     *
     * @param LoginLimitPluginInterface[] $limiters The login limit plugins to combine into one.
     */
    public function __construct(array $limiters = [])
    {
        foreach ($limiters as $limiter) {
            if (!($limiter instanceof LoginLimitPluginInterface)) {
                throw new InvalidArgumentException("LoginLimitPluginInterface expected");
            }
            $this->limiters[] = $limiter;
        }
    }

    /**
     * Decrement the number of attempts.
     *
     * This is intended for application logic when a login attempt can be forgiven.
     */
    public function decrementAttempts()
    {
        foreach ($this->limiters as $limiter) {
            $limiter->decrementAttempts();
        }
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
        $attempts = 0;
        foreach ($this->limiters as $limiter) {
            $attempts = max($limiter->getLoginAttempts(), $attempts);
        }
        return $attempts;
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
        $attempts = PHP_INT_MAX;
        foreach ($this->limiters as $limiter) {
            $attempts = min($limiter->getMaxLoginAttempts(), $attempts);
        }
        return $attempts;
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
        $result = Auth::RESULT_NOOP;
        foreach ($this->limiters as $limiter) {
            $limiterResult = $limiter->login($username, $password);

            if (in_array($limiterResult, [Auth::RESULT_FAILURE, Auth::RESULT_FORCE])) {
                return $limiterResult;
            } elseif ($limiterResult === Auth::RESULT_SUCCESS) {
                $result = $limiterResult;
            }
        }

        return $result;
    }
}
