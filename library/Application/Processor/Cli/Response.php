<?php
/**
 * Cli response
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

class Tii_Application_Processor_Cli_Response
{
    private $colors = [];// Standard CLI colors

    public function __construct()
    {
        $this->colors = array_flip([30 => 'gray', 'red', 'green', 'yellow', 'blue', 'purple', 'cyan', 'white', 'black']);
    }

    public function displayHelp()
    {
        printf("usage: php shell --timeout=0 --memory=128M module controller[index] action[index] --param=value");
        exit;
    }

    /**
     * Set process name.
     *
     * @param string $title
     * @return void
     */
    public function setProcessTitle($title)
    {
        if (function_exists('cli_set_process_title')) {// >=php 5.5
            @cli_set_process_title($title);
        } // Need proctitle when php<=5.5 .
        elseif (extension_loaded('proctitle') && function_exists('setproctitle')) {
            @setproctitle($title);
        }
    }

    public function println()
    {
        echo call_user_func_array([Tii_Application::getInstance(), 'lang'], func_get_args()). PHP_EOL;
    }

    /**
     * Color output text for the CLI, escape string with color information
     *
     * @param $text
     * @param $color
     * @param bool $bold
     * @return string
     */
    public function colorize($text, $color, $bold = false)
    {
        return"\033[" . ($bold ? '1' : '0') . ';' . $this->colors[$color] . "m$text\033[0m";
    }

    /**
     * Magic methods
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (isset($this->colors[$name])) {
            $text = array_shift($arguments);
            array_unshift($arguments, $text, $name);
            return call_user_func_array([$this, 'colorize'], $arguments);
        }
    }
}