<?php
/**
 * Exception basic class
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
 * @version $Id: Exception.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Exception extends Exception
{
    public function __construct()
    {
        $args = func_get_args();

        $message = call_user_func_array('Tii::lang', $args);
        $code = -1 * Tii_Math::hashStr(Tii::valueInArray($args, 0, ''), false);
        parent::__construct($message, $code);
    }

    /**
     * Sets a user-defined error handler function
     *
     * @see set_error_handler
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array $errcontext
     * @return bool
     */
    public static function set_error_handler($errno, $errstr, $errfile, $errline, array $errcontext = [])
    {
        $err = func_get_args();

        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_STRICT:
            case E_USER_ERROR:
                echo "<br />\n<b>Fatal error</b>:  $errstr in <b>$errfile</b> on line <b>$errline</b><br />";
                Tii_Logger::err($errstr, $err);
                exit;
                break;
            case E_WARNING:
            case E_USER_WARNING:
                Tii_Logger::warn($errstr, $err);
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                Tii_Logger::notice($errstr, $err);
                break;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                Tii_Logger::info($errstr, $err);
                break;
            default:
                Tii_Logger::debug($errstr, $err);
                break;
        }

        return true;
    }

    /**
     * @see Tii_Exception::set_error_handler
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @param array $errcontext
     * @throws ErrorException
     */
    public static function set_error_exception_handler($errno, $errstr, $errfile, $errline, array $errcontext = [])
    {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    }

    /**
     * @see set_exception_handler
     * @param $exception
     * @throws Exception
     */
    public static function set_exception_handler($exception)
    {
        throw new Exception($exception->getMessage(), $exception->getCode(), $exception);
    }
}