<?php
/**
 * Controller Template form processing helper classes
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
 * @version $Id: Template.php 8923 2017-11-19 11:49:34Z alacner $
 */

class Tii_Application_Helper_Template extends Tii_Application_Abstract
{
    private $patterns = [];
    private $replacements = [];

    public function __construct()
    {
        $filters = Tii::get('tii.application.helper.template.filters', []);

        $this->patterns = array_keys($filters);
        $this->replacements = array_values($filters);
    }

    public function compile($code)
    {
        return preg_replace($this->patterns, $this->replacements, $code);
    }

    public function file($file)
    {
        if (file_exists($file)) {
            return $this->code(file_get_contents($file));
        } else {
            throw new Tii_Application_Controller_Exception("missing template file `%s'", $file);
        }
    }

    public function code($code)
    {
        $file = Tii_Filesystem::hashfile($code, 'template', '.phtml');

        if (!file_exists($file)) {
            $ret = @file_put_contents($file, $this->compile($code));
            if ($ret == false) {
                throw new Tii_Application_Controller_Exception("unable to write to file `%s'", $file);
            }
        }

        return $file;
    }
}