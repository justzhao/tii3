<?php
/**
 * Collect the results Processor
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
 * @version $Id: Mock.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Application_Processor_Mock extends Tii_Application_Processor_Abstract
{
    private $ip;

    protected function doBusyError($loadctrl, $load)
    {
        sleep(1);
    }

    public function init()
    {
        $this->assignAll(NULL);
        $this->setPairs(NULL);
    }

    public function assign($key, $value = NULL)
    {
        return $this->setPair($key, $value, 'result');
    }

    public function assignAll($vars)
    {
        return $this->setPairs($vars, 'result');
    }

    public function get($key, $default = NULL)
    {
        return $this->getPair($key, $default, 'result');
    }

    public function getResult($default = [])
    {
        return $this->getPairs($default, 'result');
    }

    public function getResponse()
    {}

    /**
     * @param $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Get request client IP
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @see getPairs()
     */
    public function getRequests()
    {
        return $this->getPairs();
    }

    /**
     * @see getPair()
     */
    public function getRequest($name, $default = NULL)
    {
        return $this->getPair($name, $default);
    }


    /**
     * [expired = 0, [$fragment1[, ...]]
     *
     * @return string cached key
     * @throws Tii_Application_IgnoreException
     */
    public function viewCached()
    {}

    /**
     * After done *Action
     */
    public function over()
    {}
}