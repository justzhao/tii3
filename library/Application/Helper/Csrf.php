<?php
/**
 * Defense CSRF scheme
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
 * @version $Id: Csrf.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Application_Helper_Csrf extends Tii_Application_Abstract
{
    public function getName()
    {
        static $name;
        $name || $name = Tii::get('tii.application.helper.csrf.name', '__csrf_token__');
       return $name;
    }

    public function getValue()
    {
        $value = $this->getSession($this->getName());
        if (empty($value)) {
            $value = Tii_Math::random(16);
            $this->getResponse()->setSession($this->getName(), $value);
        }
        return $value;
    }

    public function getInput()
    {
        return sprintf('<input name="%s" type="hidden" value="%s" />', $this->getName(), $this->getValue());
    }

    /**
     * check csrf
     *
     * @return boolean
     */
    public function check()
    {
        $param = $this->getRequest($this->getName());
        if (empty($param)) return false;
        return ($this->getValue() === $param);
    }

    /**
     * Open CSRF, and validated
     *
     * @param bool $isPost
     * @return bool
     * @throws Tii_Application_Exception
     */
    public function validator($isPost = true)
    {
        if (($isPost && !$this->isPost()) || !$this->check()) {
            throw new Tii_Application_Exception("CSRF security error");
        }
        return true;
    }
}