<?php
/**
 * Using the local file to the cache data
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
 * @version $Id: File.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Cache_File extends Tii_Cache_Abstract
{
    private $lockers = [];
    private $configs;

    public function __construct()
    {
        $this->configs = (object) array_merge([
                'directory' => Tii::get('tii.temp_dir'),//Storage path cache files
                'gc_probality' => 1,//The GC PPM * execution probability
            ],
            Tii::get('tii.cache.file', [])
        );

        $this->configs->gc_probality = [true => $this->configs->gc_probality, false => (100-$this->configs->gc_probality)];
        Tii_Filesystem::mkdir($this->configs->directory);
    }

    /**
     * In the file driver, check to see that the cache directory is indeed writable
     *
     * @return boolean
     */
    public function isSupported()
    {
        static $isSupported;
        is_bool($isSupported) || $isSupported = Tii_Filesystem::isWritable($this->configs->directory);
        return $isSupported;
    }

    /**
     * Get cache filename, to distinguish between the different projects
     *
     * @param string $key
     * @param bool $gcEnabled
     * @return string
     */
    public function getFilename($key, $gcEnabled = false)
    {
        $filename = Tii_Filesystem::hashfile($key, 'cached');
        $gcEnabled && Tii_Math::getScaleRandom($this->configs->gc_probality) && $this->gc(dirname($filename));
        return $filename;
    }

    /**
     * Store the value in the filename (overwrite if key exists)
     *
     * @param string $key The key that will be associated with the item.
     * @param mixed $var The variable to store. Strings and integers are stored as is, other types are stored serialized.
     * @param int $compress Use file to store the item compressed (uses zlib).
     * @param int $expire Expiration time of the item. If it's equal to zero, the item will never expire. You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
     * @return bool
     */
    public function set($key, $var, $compress = 0, $expire = 0)
    {
        $expire || $expire = 31536000;//one year
        $data = [
            'expired' => time() + $expire,
            'data' => $var,
        ];
        $cacheFile = $this->getFilename($key, true);
        file_put_contents($cacheFile, serialize($data), LOCK_EX);
        clearstatcache();
        return Tii_Filesystem::touch($cacheFile, $data['expired']);
    }

    /**
     * Stores variable var with key only if such key doesn't exist at the server yet.
     *
     * @param string $key The key that will be associated with the item.
     * @param mixed $var The variable to store. Strings and integers are stored as is, other types are stored serialized.
     * @param int $compress Use file to store the item compressed (uses zlib).
     * @param int $expire Expiration time of the item. If it's equal to zero, the item will never expire. You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
     * @return bool
     */
    public function add($key, $var, $compress = 0, $expire = 0) {
        if (is_file($this->getFilename($key))) return false;
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
    public function replace($key, $var, $compress = 0, $expire = 0) {
        if (!is_file($this->getFilename($key))) return false;
        return $this->set($key, $var, $compress, $expire);
    }

    /**
     * Increment item's value.
     *
     * @param string $key Key of the item to increment.
     * @param int $value Increment the item by value.
     * @return bool
     */
    public function increment($key, $value = 1) {
        $value = intval($this->get($key)) + $value;
        return $this->set($key, $value);
    }

    /**
     * Decrements value of the item by value.
     *
     * @param string $key Key of the item do decrement.
     * @param int $value Decrement the item by value
     * @return bool
     */
    public function decrement($key, $value = 1) {
        $value = intval($this->get($key)) - $value;
        return $this->set($key, $value);
    }

    /**
     * Returns previously stored data if an item with such key exists on the server at this moment. You can pass array of keys to get array of values. The result array will contain only found key-value pairs.
     *
     * @param mixed $key The key or array of keys to fetch.
     * @return mix
     */
    public function get($key)
    {
        if (is_array($key)) {
            $values = [];
            foreach ($key as $k) {
                $values[$k] = $this->get($k);
            }
            return $values;
        }

        $cacheFile = $this->getFilename($key);
        if (!is_file($cacheFile)) return NULL;

        $string = file_get_contents($cacheFile);
        $data = unserialize($string);
        if (!isset($data['expired'])) {
            $this->delete($key);
            return NULL;
        }
        if ($data['expired'] && $data['expired'] < time()) {
            $this->delete($key);
            return NULL;
        }
        return $data['data'];
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
        return Tii_Filesystem::unlink($this->getFilename($key));
    }

    /**
     * lock
     * @param $key
     * @param $expire
     * @return bool
     */
    public function lock($key, $expire = 60)
    {
        $locker = fopen($this->getFilename($key), "w+");
        if ($locker === false) return false;
        ignore_user_abort(true);
        $this->lockers[$key] = $locker;
        return flock($locker, LOCK_EX);
    }

    /**
     * unlock
     * @param $key
     * @return bool
     */
    public function unlock($key)
    {
        $unlocked = false;
        if (isset($this->lockers[$key]) && ($locker = $this->lockers[$key]) !== false) {
            $unlocked = flock($locker, LOCK_UN);
            fclose($locker);
        }
        return $unlocked;
    }

    /**
     * @param $path
     */
    protected function gc($path)
    {
        foreach(Tii_Filesystem::getFiles($path) as $file) {
            if (time() < filemtime($file)) continue;
            Tii_Filesystem::unlink($file);
        }
    }

    /**
     * Flush all existing items at the server
     *
     * @return void
     */
    public function flush()
    {
        foreach (Tii_Filesystem::getFiles($this->configs->directory) as $file) {
            Tii_Filesystem::unlink($file);
        }
        foreach (Tii_Filesystem::getFolders($this->configs->directory) as $folder) {
            Tii_Filesystem::rmdir($folder);
        }
    }
}