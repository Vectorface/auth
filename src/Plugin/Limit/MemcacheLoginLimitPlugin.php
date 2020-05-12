<?php
/** @noinspection PhpComposerExtensionStubsInspection */

namespace Vectorface\Auth\Plugin\Limit;

use Memcache;
use Vectorface\Auth\Auth;
use Vectorface\Auth\Plugin\BaseAuthPlugin;
use Vectorface\Auth\Plugin\SharedLoggerTrait;

/**
 * Limit the number of logins allowed per login and IP-address in memcache(d)
 *
 * Notes:
 *  - The attempts per-login are visible via the public interfaces, but per-IP are not.
 *  - Per-login attempt limiting is meant to catch brute-force attempts on a single user.
 *  - Per-IP limiting is meant to catch and prevent floods.
 *
 * Todo:
 *  - There should be a fallback mechanism for this if the memcache server is not available.
 *  - Configurable logging mechanism should be introduced to be able to do more useful alerts.
 */
class MemcacheLoginLimitPlugin extends BaseAuthPlugin implements LoginLimitPluginInterface
{
    /**
     * Allows use of a logger attached to the auth class, if configured.
     */
    use SharedLoggerTrait;

    /**
     * Default maximum number of login attempts.
     */
    const MAX_LOGIN = 3;

    /**
     * Default maximum number of attempts per IP address.
     */
    const MAX_ADDR = 10;

    /**
     * Default timeout in seconds.
     */
    const TIMEOUT = 300;

    /**
     * The maximum allowed number of login attempts within the timeout.
     *
     * @var int
     */
    protected $maxAttemptsLogin;

    /**
     * The maximum allowed number of attempts from a given address within the timeout.
     *
     * @var int
     */
    protected $maxAttemptsAddr;

    /**
     * The amount of time that must pass after the maximum number of failed login attempts.
     *
     * @var int
     */
    protected $timeout;

    /**
     * The memcache instance to be used to store the timeout.
     *
     * @var Memcache
     */
    protected $memcache;

    /**
     * The number of attempts so far for this login.
     *
     * @var int
     */
    protected $attemptsLogin;

    /**
     * The number of attempts so far from this IP address.
     *
     * @var int
     */
    protected $attemptsAddr;

    /**
     * The unique IP address or hostname associated with the login attempts.
     *
     * @var string
     */
    protected $addr;

    /**
     * Stores the last username to be used for a login attempt.
     *
     * @var string
     */
    protected $username;

    /**
     * Create a new memcache login limiter.
     *
     * @param Memcache $mc The pre-configured Memcache handle.
     * @param int $login The maximum number of login attempts to be allowed.
     * @param int $addr The maximum number of login attempts per IP address.
     * @param int $sec The amount of time (in seconds) to block login attempts after max attempts has been surpassed.
     */
    public function __construct(Memcache $mc, $login = self::MAX_LOGIN, $addr = self::MAX_ADDR, $sec = self::TIMEOUT)
    {
        $this->memcache = $mc;
        $this->maxAttemptsLogin = (int)$login;
        $this->maxAttemptsAddr = (int)$addr;
        $this->timeout = (int)$sec;
    }

    /**
     * Set the remote address to be used. Falls back to PHP's REMOTE_ADDR.
     *
     * Note: This can be any string; a hostname, an IPv4 or IPv6 address or network, or even a hash.
     *
     * @param string $addr A unique string that maps to the address making login requests.
     */
    public function setRemoteAddr($addr)
    {
        $this->addr = $addr;
    }

    /**
     * Decrement the number of attempts.
     *
     * This is intended for application logic when a login attempt can be forgiven.
     */
    public function decrementAttempts()
    {
        $this->setLoginAttempts($this->username, false);
    }

    /**
     * Get the memcache cache key.
     *
     * @param string $username The username attempting to log in.
     * @return string[] A pair of cache keys for login and address tracking.
     */
    protected function getKeys($username)
    {
        $addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'localhost';
        return [
            sprintf('LoginLimit::login(%s)', $username),
            sprintf('LoginLimit::addr(%s)', $this->addr ? $this->addr : $addr)
        ];
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
        return $this->attemptsLogin ?? 0;
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
        return $this->maxAttemptsLogin;
    }

    /**
     * Adjust the number of attempts in a given memcache key.
     *
     * @param string $key The memcache key to alter.
     * @param bool $inc True to increment, false to decrement.
     * @return false|int
     */
    protected function setAttemptKey($key, $inc)
    {
        /* Increment or decrement, as appropriate. */
        $result = $inc ? $this->memcache->increment($key) : $this->memcache->decrement($key);

        /* If inc/dec fails, set the value directly. */
        if ($result === false) {
            $result = $inc ? 1 : 0; /* No value, so either reset to 0, or set to 1. */
            if (!$this->memcache->set($key, $result, null, $this->timeout)) {
                $this->warning('Setting login attempt count in memcache failed. Login throttling may be broken.');
            }
        }

        return $result;
    }

    /**
     * Used internally to set the number of login attempts in memcache.
     *
     * @param string $username The username attempting to log in.
     * @param bool $inc True to increment the number of attempts, false to decrement.
     * @return int[] The number of attempts for the given username and IP address.
     */
    protected function setLoginAttempts($username, $inc)
    {
        list($keyLogin, $keyAddr) = $this->getKeys($username);

        if (!empty($username)) {
            $this->attemptsLogin = (int)$this->setAttemptKey($keyLogin, $inc);
        }
        $this->attemptsAddr = (int)$this->setAttemptKey($keyAddr, $inc);

        return [$this->attemptsLogin, $this->attemptsAddr];
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
        $this->username = $username; /* Store the last-used username */

        list($login, $addr) = $this->setLoginAttempts($username, true);

        if ($login > $this->maxAttemptsLogin || $addr > $this->maxAttemptsAddr) {
            return Auth::RESULT_FAILURE;
        }

        return Auth::RESULT_NOOP;
    }
}
