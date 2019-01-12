<?php
/**
 * HTTP response
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
 * @version $Id: Response.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Application_Processor_Http_Response
{
    private $headers = [];

    public function __construct()
    {
        $this->setHeader('Tii-Version', Tii_Version::VERSION, true);
    }

    public function isResponsed()
    {
        return headers_sent();
    }

    public function done()
    {
        if ($this->isResponsed()) {
            return false;
        }

        foreach ($this->getHeaders() as $protocol => $values) {
            is_array($values) || $values = [$values];

            foreach ($values as $value) {
                header("$protocol: $value", false);
            }
        }
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function setHeader($protocol, $value = NULL, $onlyDebugMode = false)
    {
        if ($onlyDebugMode && !Tii_Config::isDebugMode()) {
            return false;
        }
        if (is_null($value)) {//protocol decided to value
            switch(strtoupper($protocol)) {
                case 'P3P'://Platform for Privacy Preferences
                    $value = 'CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"';
                    break;
                default:
            }
        }
        return $this->setHeaders([$protocol => $value]);
    }

    public function setCookie()
    {
        $paramArr = func_get_args();
        $paramArr[0] = str_replace('.', '_', $paramArr[0]);
        if ($paramArr[0]{0} === '_') $paramArr[1] = Tii_Security_Encryption::encode($paramArr[1]);//$value
        return call_user_func_array('setcookie', $paramArr);
    }

    public function setSession($name, $value = NULL)
    {
        if (!Tii_Application_Session::isStarted()) {
            throw new Tii_Application_Exception("session was not successfully started");
        }
        if (is_null($value)) {
            unset($_SESSION[$name]);
            return true;
        }
        $_SESSION[$name] = $value;
        return true;
    }

    /**
     * Usage: ->unsetSession('name1','name2',...), also use setSession('name1', NULL),setSession('name2',null),...
     *
     * @return bool
     * @throws Tii_Application_Exception
     */
    public function unsetSession()
    {
        if (!Tii_Application_Session::isStarted()) {
            throw new Tii_Application_Exception("session was not successfully started");
        }

        $paramArr = func_get_args();
        foreach ($paramArr as $name) {
            unset($_SESSION[$name]);
        }
        return true;
    }

    /**
     * Sending Http status information
     *
     * @param int $state http state code
     * @param bool $print
     */
    public function sendHttpStatus($state = 200, $print = false)
    {
        if ($status = Tii_Http::getHttpStatus($state)) {
            header('HTTP/1.1 ' . $state . ' ' . $status);
            header('Status:' . $state . ' ' . $status);//确保FastCGI模式下正常
            if ($print) echo $status;
        }
    }

    /**
     * Send Force Download Header
     *
     * @param $filesize
     * @param $filename
     * @param $contentType
     */
    public function sendForceDownload($filename, $filesize = 0, $contentType = 'application/octet-stream')
    {
        if ($filesize > 0) $this->setHeader('Content-length', $filesize);
        $this->setHeader('Content-Type', $contentType);
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Escape a string to prevent XSS
     * @see printf
     */
    public function output($string, $type = 'html', $return = false)
    {
        switch ($type) {
            case 'html' :
                $string = Tii_Security_Filter::htmlChars($string);
                break;
            case 'javascript' :
                $string = Tii_Security_Filter::jsChars($string);
                break;
            default:
                $string = Tii_Security_Filter::str($string);
                break;
        }

        if ($return) return $string;
        return print $string;
    }

    /**
     * Output via lang
     */
    public function i18n()
    {
        return $this->output(call_user_func_array([Tii_Application::getInstance(), 'lang'], func_get_args()));
    }

    /**
     * Output via url
     */
    public function link()
    {
        return $this->output(call_user_func_array([Tii_Application::getInstance(), 'url'], func_get_args()));
    }
}