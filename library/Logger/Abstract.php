<?php
/**
 * Log processing abstract classes
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

abstract class Tii_Logger_Abstract
{
    protected $priority = Tii_Logger_Constant::ERR;
    protected $priorities = [];
    protected $priorityNames = [];

    public function __construct()
    {
        list($this->priorities, $this->priorityNames) = Tii::_constants('Tii_Logger_Constant');
    }

    /**
     * Set the logging level
     * @param int $priority
     */
    public function setPriority($priority = Tii_Logger_Constant::ERR)
    {
        $this->priority = $priority;
    }

    /**
     * Get the logging level
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * The calling function of constructing various error types
     * @param string $name
     * @param array $arguments
     * @return bool
     */
    public function __call($name, $arguments)
    {
        list($message, $extras) = array_pad($arguments, 2, NULL);
        $priority = Tii::valueInArray($this->priorities, strtoupper($name), Tii_Logger_Constant::ALL);

        Tii_Logger::$print && printf("[%s] %s\n", $name, $message);
        $priority > Tii_Logger::$print_backtrace_priority || debug_print_backtrace();
        if ($priority > $this->priority) return false;

        try {
            return call_user_func([$this, 'doLog'], $message, $priority, $extras);
        } catch (Exception $e) {
            trigger_error("send the log records failed: " . $e->getMessage(), E_USER_NOTICE);
            return false;
        }
    }

    /**
     * According to the type of type name
     * @param int $priority
     * @return string|null
     */
    public function getPriorityName($priority)
    {
        return Tii::valueInArray($this->priorityNames, $priority);
    }

    /**
     * Logging constructor
     * @param string $message
     * @param int $priority
     * @param mixed $extras
     */
    abstract public function doLog($message, $priority = Tii_Logger_Constant::ERR, $extras = NULL);
}