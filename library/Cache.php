<?php
/**
 * Cache class key/value store
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
 * @version $Id: Cache.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Cache
{
    private $classNames = [];

    public function __construct()
    {
        call_user_func_array([$this, 'setChain'], Tii::get('tii.cache.chain', ['memcache', 'apc', 'file']));
    }

    /**
     * Set the cache processing chain
     *
     * @return Tii_Cache
     */
    public function setChain()
    {
        foreach(array_unique(func_get_args()) as $driver) {
            if ($driver{0} == '@') {
                $this->classNames[] = substr($driver, 1);
            } else {
                $this->classNames[] = Tii::className('Tii', 'Cache', $driver);
            }
        }
        return $this;
    }

    /**
     * According to the callback function for caching
     * php-fpm.conf:
     *   pm.max_children = 50 <-- Increase the value
     *   pm.start_servers = 20 <-- Increase the value
     *
     * @param $cacheName
     * @param int $expired
     * @param NULL $function
     * @param array $param_arr
     * @param int $retry
     * @return mixed
     */
    public function cached($cacheName, $expired = 0, $function = NULL, array $param_arr = [], $retry = 5)
    {
        try {
            return call_user_func_array([$this, '_cached'], func_get_args());
        } catch (Exception $e) {
            Tii_Logger::warn("cache `$cacheName' exception:". $e->getMessage());
            return Tii::call($function, $param_arr);
        }
    }

    private function _cached($cacheName, $expired = 0, $function = NULL, array $param_arr = [], $retry = 5)
    {
        if ($expired < 0) {//Remove cached file less than 0
            $this->delete($cacheName);
            return Tii::call($function, $param_arr);
        }

        $data = $this->get($cacheName);//try get via cache
        $founded = isset($data['data']);

        if ($founded && (($data['expired'] - time()) < 15)) {//To be outdated data updated
            Tii_Filesystem::locker($cacheName . '.expired.lock', function() use (&$founded) {
                $founded = false;
            });
        }

        if ($founded) return $data['data'];

        Tii_Filesystem::locker($cacheName . '.cached.lock',
            function() use (&$data, $cacheName, $function, $param_arr, $expired) {
                ignore_user_abort(true);
                $data = [
                    'data' => Tii::call($function, $param_arr),
                    'expired' => time() + $expired,
                ];
                $this->set($cacheName, $data, 0, $expired);
                ignore_user_abort(false);
            }, function() use (&$data, $cacheName, $expired, $function, $param_arr, $retry){
                if ($retry--) {
                    usleep(round(rand(10, 100) * 1000 * $retry)); //0-100 miliseconds
                    $data = $this->cached($cacheName, $expired, $function, $param_arr, $retry);
                }
            }
        );

        return Tii::valueInArray($data, 'data');
    }

    /**
     * Proxy factory
     */
    public function __call($name, $arguments)
    {
        foreach($this->classNames as $className) {
            $that = Tii::object($className);
            if (($that instanceof Tii_Cache_Abstract) && $that->isSupported()) {
                return call_user_func_array([$that, $name], $arguments);
            }
        }
        throw new Tii_Exception("effective cache in chain %s not found", json_encode($this->classNames));
    }
}