<?php
/**
 * Cache protocol session of abstract classes
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

class Tii_Application_SessionHandler_Cache implements SessionHandlerInterface
{
    private $maxlifetime = 0;
    private $cache; /* @var $cache Tii_Cache_Abstract */
    private $prefix = 'tii.session.';


    public function __construct()
    {
        $this->cache = Tii::object('Tii_Cache');
        $this->maxlifetime = ini_get('session.gc_maxlifetime');
    }

    public function open($save_path, $session_id)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($session_id)
    {
        return $this->cache->get($this->prefix . $session_id);
    }

    public function write($session_id, $session_data)
    {
        return $this->cache->set($this->prefix . $session_id, $session_data, 0, $this->maxlifetime);
    }

    public function destroy($session_id)
    {
        return $this->cache->delete($this->prefix . $session_id);
    }

    public function gc($maxlifetime)
    {
        return true;
    }
}