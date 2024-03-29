<?php
/** @noinspection PhpComposerExtensionStubsInspection */

namespace Vectorface\Tests\Auth\Helpers;

/**
 * Fake some memcache functionality.
 */
class FakeMemcachePHP73 extends \Memcache
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
    public function get($key, &$flags = null, &$unused = null)
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
    public function add($key, $value, $flags = null, $ttl = 0)
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
    public function set($key, $value, $flags = null, $ttl = 0)
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
    public function flush()
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
    public function replace($key, $value, $flag = null, $expire = 0)
    {
        if ($this->broken) {
            return false;
        }
        $old = $this->get($key);
        if ($old === false) {
            return false;
        }

        return $this->set($key, $value, $flag, $expire);
    }

    /**
     * Mimic Memcache::increment
     *
     * @see http://php.net/manual/en/memcache.increment.php
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public function increment($key, $value = 1)
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
    public function decrement($key, $value = 1)
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
    public function delete($key, $timeout = 0)
    {
        if ($this->broken) {
            return false;
        }
        unset(static::$cache[$key]);
        return true;
    }
}
