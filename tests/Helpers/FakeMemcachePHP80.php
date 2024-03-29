<?php
/** @noinspection PhpComposerExtensionStubsInspection */

namespace Vectorface\Tests\Auth\Helpers;

/**
 * Fake some memcache functionality.
 */
class FakeMemcachePHP80 extends \Memcache
{
    /**
     * An array to fake "memcache" key/value store for the duration of the script.
     *
     * @var mixed[]
     */
    public static $cache = [];

    /**
     * A flag to indicate that this class should act as if all operations fail.
     *
     * @var bool
     */
    public $broken = false;

    /**
     * Mimic Memcache::get
     *
     * @see http://php.net/manual/en/memcache.get.php
     * @param string $key
     * @param int|null $flags
     * @param null $unused
     * @return array|bool|mixed
     */
    public function get(array|string $key, mixed &$flags = null, mixed &$cas = null): mixed
    {
        if ($this->broken) {
            return false;
        }

        if (is_array($key)) {
            $values = [];
            foreach ($key as $k) {
                $values[$k] = $this->get($k);
            }
            return $values;
        }

        return static::$cache[$key] ?? false;
    }

    /**
     * Mimic Memcache::add
     *
     * @see http://php.net/manual/en/memcache.add.php
     * @param string $key
     * @param mixed $value
     * @param int|null $flags
     * @param int $ttl
     * @return bool
     */
    public function add(array|string $key, mixed $value = null, int $flags = null, int $exptime = null, int $cas = null): bool
    {
        if ($this->broken) {
            return false;
        }

        // Do nothing if the key already exists
        if (isset(static::$cache[$key])) {
            return false;
        }

        static::$cache[$key] = $value;
        return true;
    }

    /**
     * Mimic Memcache::set
     *
     * @see http://php.net/manual/en/memcache.set.php
     * @param string $key
     * @param mixed $value
     * @param int|null $flags
     * @param int $ttl
     * @return bool
     */
    public function set(array|string $key, mixed $value = null, int $flags = null, int $exptime = null, int $cas = null): bool
    {
        if ($this->broken) {
            return false;
        }

        static::$cache[$key] = $value; // $ttl is ignored.
        return true;
    }

    /**
     * Mimic Memcache::flush
     *
     * @see http://php.net/manual/en/memcache.flush.php
     */
    public function flush(int $delay = null): bool
    {
        if ($this->broken) {
            return false;
        }
        static::$cache = [];
        return true;
    }

    /**
     * Mimic Memcache::replace
     *
     * @see http://php.net/manual/en/memcache.replace.php
     * @param string $key
     * @param mixed $value
     * @param int|null $flag
     * @param int $expire
     * @return bool
     */
    public function replace(array|string $key, mixed $value = null, int $flags = null, int $exptime = null, int $cas = null): bool
    {
        if ($this->broken) {
            return false;
        }
        $old = $this->get($key);
        if ($old === false) {
            return false;
        }

        return $this->set($key, $value, $flags, $exptime);
    }

    /**
     * Mimic Memcache::increment
     *
     * @see http://php.net/manual/en/memcache.increment.php
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public function increment(array|string $key, int $value = 1, int $defval = null, int $exptime = null): array|int|bool
    {
        if ($this->broken) {
            return false;
        }

        $old = $this->get($key);
        if ($old === false) {
            return false;
        } elseif (!is_numeric($old)) {
            $old = 0;
        }

        return $this->set($key, $old + $value) ? $this->get($key) : false;
    }

    /**
     * Mimic Memcache::decrement
     *
     * @see http://php.net/manual/en/memcache.decrement.php
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public function decrement(array|string $key, int $value = 1, int $defval = null, int $exptime = null): array|int|bool
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * Mimic Memcache::delete
     *
     * @see http://php.net/manual/en/memcache.delete.php
     * @param string $key
     * @param int $timeout
     * @return bool
     */
    public function delete(array|string $key, int $timeout = 0): bool
    {
        if ($this->broken) {
            return false;
        }
        unset(static::$cache[$key]);
        return true;
    }
}
