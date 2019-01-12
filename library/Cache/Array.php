<?php
/**
 * Using the PHP Array to Cache data in runtime
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
 * @version $Id: Array.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Cache_Array extends Tii_Cache_Abstract
{
    private $expiredTimes = [];//key => expired
    private $data = [];//key => val

    private function clearExpired()
    {
        $nt = microtime(true);
        foreach($this->expiredTimes as $k => $t) {
            $t -= $nt;
            if (!$t) {
                unset($this->expiredTimes[$k], $this->data[$k]);
            }
        }
    }

    /**
     * is_supported()
     */
    public function isSupported()
    {
        return true;
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
        if ($expire) {
            $this->cachedTime[$key] = microtime(true) + $expire;
        }

        $this->data[$key] = $var;

        $this->clearExpired();
        return true;
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
        if (isset($this->data[$key])) return false;
        return $this->set($key, $var, $compress, $expire);
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
        if (!isset($this->data[$key])) return false;
        return $this->set($key, $var, $compress, $expire);
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
        if (!isset($this->data[$key])) return false;
        $this->data[$key] += $value;
        return true;
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
        if (!isset($this->data[$key])) return false;
        $this->data[$key] -= $value;
        return true;
    }

    /**
     * Returns previously stored data if an item with such key exists on the server at this moment. You can pass array of keys to get array of values. The result array will contain only found key-value pairs.
     *
     * @param mixed $key The key or array of keys to fetch.
     * @return mix
     */
    public function get($key)
    {
        $this->clearExpired();
        return $this->data[$key];
    }

    /**
     * Delete item from the server
     *
     * @param string $key The key associated with the item to delete.
     * @param int $timeout This deprecated parameter is not supported, and defaults to 0 seconds. Do not use this parameter.
     * @return bool
     */
    public function delete($key, $timeout = 0)
    {
        if ($timeout) {
            $this->cachedTime[$key] = microtime(true) + $timeout;
        } else {
            unset($this->data[$key]);
        }

        $this->clearExpired();
        return true;
    }

    /**
     * lock
     * @param $key
     * @param $expire
     * @return bool
     */
    public function lock($key, $expire = 60)
    {
        if ($this->get($key)) {
            return false;
        }
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
        $this->expiredTimes = [];
        $this->data = [];
    }
}