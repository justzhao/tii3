<?php
/**
 * Application abstract class
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
 * @version $Id: Abstract.php 8915 2017-11-05 03:38:45Z alacner $
 */

abstract class Tii_Application_Abstract
{
    /**
     * Magic methods
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (is_callable([Tii_Application::getInstance(), $name])) {
            return call_user_func_array([Tii_Application::getInstance(), $name], $arguments);
        }
    }

    /**
     * Magic to get a exist value from processor
     *
     * @param $name
     * @return mixed
     * @throws Tii_Application_Controller_Exception
     */
    public function __get($name)
    {
        $value = $this->getProcessor()->getRequest($name);
        if (is_null($value)) {
            throw new Tii_Application_Controller_Exception("parameter `%s' not exist", $name);
        }
        return $value;
    }

    /**
     * Magic to set a value to processor
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value = NULL)
    {
        $this->getProcessor()->{$name} = $value;
    }

    /**
     * Check Value Exist
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return !is_null($this->getProcessor()->getRequest($name));
    }
}