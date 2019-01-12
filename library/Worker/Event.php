<?php
/**
 * Worker Event
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
 * @version $Id: Event.php 8921 2017-11-12 13:46:59Z alacner $
 */

class Tii_Worker_Event
{
    /**
     * Read event.
     *
     * @var int
     */
    const EV_READ = 1;

    /**
     * Write event.
     *
     * @var int
     */
    const EV_WRITE = 2;

    /**
     * Except event
     *
     * @var int
     */
    const EV_EXCEPT = 3;

    /**
     * Signal event.
     *
     * @var int
     */
    const EV_SIGNAL = 4;

    /**
     * Timer event.
     *
     * @var int
     */
    const EV_TIMER = 8;

    /**
     * Timer once event.
     *
     * @var int
     */
    const EV_TIMER_ONCE = 16;

    /**
     * Get event instance
     *
     * @param $name
     * @param $arguments
     * @return Tii_Worker_Event_Abstract
     */
    public static function __callStatic($name, $arguments)
    {
        return Tii::objective(self::getClassName($name == 'instance' ? self::getDriverName() : $name), $arguments);
    }

    /**
     * Get class name
     *
     * @param $driverName
     * @return string
     */
    protected static function getClassName($driverName)
    {
        return Tii::className(__CLASS__, $driverName);
    }

    /**
     * Get event loop name.
     *
     * @return string
     */
    public static function getDriverName()
    {
        static $driverName;
        if ($driverName) return $driverName;

        $driverName = Tii::get('tii.worker.default_event', Tii_Worker::$init['default_event']);
        foreach (Tii::get('tii.worker.available_events', Tii_Worker::$init['available_events']) as $name) {
            if (extension_loaded($name)) {
                $driverName = $name;
                break;
            }
        }
        return $driverName;
    }
}