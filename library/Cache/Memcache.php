<?php
/**
 * Memcache class
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2005 - 2017, Fitz Zhang <alacner@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Memcache.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Cache_Memcache extends Tii_Cache_Abstract
{
    private $memcache;
    private $isSupported = false;

    public function __construct()
    {
        if (class_exists('Memcache')) {
            $this->memcache = new Memcache;

            $configs = (array)Tii::get('tii.cache.memcache', []);

            $successed = false;
            foreach ($configs as $server => $config) {
                if (call_user_func_array([$this->memcache, 'addServer'], $config)) {
                    $successed = true;
                }
            }
            if ($successed) {
                $this->isSupported = true;
            } else {
                $this->isSupported = $this->memcache->set("Tii_Cache_Memcache.isSupported", true);
            }
        }
    }

    /**
     * Returns FALSE if memcached is not supported on the system.
     * If it is, we setup the memcached object & return TRUE
     */
    public function isSupported()
    {
        return $this->isSupported;
    }

    /**
     * Add a memcached server to connection pool
     *
     * @param string $host Point to the host where memcached is listening for connections. This parameter may also specify other transports like unix:///path/to/memcached.sock to use UNIX domain sockets, in this case port must also be set to 0.
     * @param int $port Point to the port where memcached is listening for connections. Set this parameter to 0 when using UNIX domain sockets.
     * @param bool $persistent Controls the use of a persistent connection. Default to TRUE.
     */
    public function addServer()
    {
        $config = func_get_args();
        return call_user_func_array([$this->memcache, 'addServer'], $config);
    }

    /**
     * Store the value in the memcache memory (overwrite if key exists)
     *
     * @param string $key The key that will be associated with the item.
     * @param mixed $var The variable to store. Strings and integers are stored as is, other types are stored serialized.
     * @param int $compress Use MEMCACHE_COMPRESSED to store the item compressed (uses zlib).
     * @param int $expire Expiration time of the item. If it's equal to zero, the item will never expire. You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
     * @return bool
     */
    public function set($key, $var, $compress = 0, $expire = 0)
    {
        return $this->memcache->set($key, $var, $compress ? MEMCACHE_COMPRESSED : NULL, $expire);
    }

    /**
     * Stores variable var with key only if such key doesn't exist at the server yet.
     *
     * @param string $key The key that will be associated with the item.
     * @param mixed $var The variable to store. Strings and integers are stored as is, other types are stored serialized.
     * @param int $compress Use MEMCACHE_COMPRESSED to store the item compressed (uses zlib).
     * @param int $expire Expiration time of the item. If it's equal to zero, the item will never expire. You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
     * @return bool
     */
    public function add($key, $var, $compress = 0, $expire = 0)
    {
        return $this->memcache->add($key, $var, $compress ? MEMCACHE_COMPRESSED : NULL, $expire);
    }

    /**
     * Replace value of the existing item.
     *
     * @param string $key The key that will be associated with the item.
     * @param mixed $var The variable to store. Strings and integers are stored as is, other types are stored serialized.
     * @param int $compress Use MEMCACHE_COMPRESSED to store the item compressed (uses zlib).
     * @param int $expire Expiration time of the item. If it's equal to zero, the item will never expire. You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
     * @return bool
     */
    public function replace($key, $var, $compress = 0, $expire = 0)
    {
        return $this->memcache->replace($key, $var, $compress ? MEMCACHE_COMPRESSED : NULL, $expire);
    }

    /**
     * Increment item's value.
     *
     * @param string $key Key of the item to increment.
     * @param int $value Increment the item by value.
     * @return bool
     */
    public function increment($key, $value = 1)
    {
        return $this->memcache->increment($key, intval($value));
    }

    /**
     * Decrements value of the item by value.
     *
     * @param string $key Key of the item do decrement.
     * @param int $value Decrement the item by value
     * @return bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->memcache->decrement($key, intval($value));
    }

    /**
     * Returns previously stored data if an item with such key exists on the server at this moment. You can pass array of keys to get array of values. The result array will contain only found key-value pairs.
     *
     * @param mixed $key The key or array of keys to fetch.
     * @return mixed
     */
    public function get($key)
    {
        return $this->memcache->get($key);
    }

    /**
     * Delete item from the server
     *
     * @param string $key The key associated with the item to delete.
     * @param int $timeout This deprecated parameter is not supported, and defaults to 0 seconds. Do not use this parameter.
     * @return bool
     */
    public function delete($key, $timeout=0)
    {
        return $this->memcache->delete($key, $timeout);
    }

    /**
     * lock
     * @param $key
     * @return bool
     */
    public function lock($key, $expire = 60)
    {
        if ($this->get($key)) {
            return false;
        }
        ignore_user_abort(true);
        return $this->set($key, true, 0, $expire);
    }

    /**
     * unlock
     * @param $key
     * @return bool
     */
    public function unlock($key)
    {
        if (!$this->get($key)) {
            return false;
        }
        return $this->delete($key);
    }

    /**
     * Flush all existing items at the server
     *
     * @return void
     */
    public function flush()
    {
        $this->memcache->flush();
    }

    /**
     * Get server pool statistics
     *
     * @return array
     */
    public function getExtendedStats($type = NULL, $slabid = NULL, $limit = 100)
    {
        return $this->memcache->getStats($type, $slabid, $limit);
    }
}