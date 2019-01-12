<?php
/**
 * Processor http
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
 * @version $Id: Http.php 8930 2017-11-23 14:20:25Z alacner $
 */

class Tii_Application_Processor_Httpd extends Tii_Application_Processor_Http
{
    /**
     * After done *Action
     */
    public function over()
    {
        ob_start();
        ob_implicit_flush(false);
        parent::over();
        Tii_Event::action('tii.application.processor.http.display', $this, ob_get_clean());
        $this->getDispatcher()->getProcessor(true);
    }

    /**
     * Response sth.
     * @see call_user_func
     */
    public function callResponseFunc()
    {
        $args = func_get_args();
        $function = array_shift($args);
        call_user_func_array($function, $args);
    }
}