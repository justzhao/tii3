<?php
/**
 * Attach (or remove) multiple callbacks to an event and trigger those callbacks when that event is called.
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
 * @version $Id: Event.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_Event
{
    private static $events = [];//[event][priority][] => callback
    private static $files = [];//[event][] => path
    private static $interrupts = [];//[event] => bool

    /**
     * initialization
     */
    public static function init()
    {
        $filters = Tii::get('tii.application.filters', []);

        if (isset($filters['*'])) {//filter directories
            $filterDirs = $filters['*'];
            is_array($filterDirs) || $filterDirs = [$filterDirs];
            foreach($filterDirs as $dir) {
                if (is_dir($dir)) {
                    foreach(Tii_Filesystem::getRelativePathFiles($dir, ['php']) as $filename) {
                        list($eventName) = Tii_Filesystem::explode(substr($filename, 0, -4));
                        Tii_Event::register($eventName, Tii_Filesystem::concat($dir, $filename));
                    }
                } else {
                    Tii_Logger::warn("filter directory `$dir' not exist");
                }
            }
            unset($filters['*']);
        }

        //other filters
        foreach($filters as $event => $callbacks) {
            is_array($callbacks) || $callbacks = [$callbacks];
            foreach($callbacks as $callback) {
                Tii_Event::register($event, $callback);
            }
        }
    }

    /**
     * Register a event
     *
     * @param string $event name
     * @param callable $callback the method or function to call / to include callback file
     * @param int $priority Default 1024, Sort hook by reverse order, [n->0]
     * @return bool
     * @throws Tii_Exception
     */
    public static function register($event, $callback = NULL, $priority = 1024)
    {
        if (is_callable($callback)) {
            self::$events[$event][$priority][] = $callback;
            krsort(self::$events[$event], SORT_NUMERIC);
        } else if (is_file($callback)) {
            self::$files[$event][] = $callback;
        } else {
            throw new Tii_Exception("register `%s' failed, invalid argument `callback'", $event);
        }

        return true;
    }

    /**
     * Import callback methods via files
     *
     * @param $event
     * @return NULL|array
     */
    private static function import($event)
    {
        if (!isset(self::$files[$event])) return NULL;
        $files = self::$files[$event];
        foreach($files as $file) {
            include_once $file;
        }
        unset(self::$files[$event]);
        return $files;
    }

    /**
     * Interrupt the filter or action
     *
     * @param string $event name
     */
    public static function interrupt($event)
    {
        self::$interrupts[$event] = true;
    }

    /**
     * Trigger filter callbacks
     * Usage:
     * $a = ::filter('event_name', $a[, $b,...]);
     *
     * @param string $event name
     * @param mixed $value the optional value to pass to each callback
     * @return mixed
     */
    public static function filter($event, $value = NULL)
    {
        $args = func_get_args();
        $event = array_shift($args);

        self::import($event);
        if (!isset(self::$events[$event])) return $value;

        foreach (self::$events[$event] as $callbacks) {
            foreach ($callbacks as $callback) {
                if (Tii::valueInArray(self::$interrupts, $event, false)) break 2;
                $args[0] = call_user_func_array($callback, $args);
            }
        }
        return $args[0];
    }

    /**
     * Loop callbacks until got non-null value
     * args:[event[, arg1[,...]]]
     *
     * @return mixed
     */
    public static function action()
    {
        $args = func_get_args();
        $event = array_shift($args);

        self::import($event);
        if (!isset(self::$events[$event])) return NULL;

        $return = [];
        foreach (self::$events[$event] as $callbacks) {
            foreach ($callbacks as $callback) {
                if (Tii::valueInArray(self::$interrupts, $event, false)) break 2;
                $return[] = call_user_func_array($callback, $args);
            }
        }

        return $return;
    }

    /**
     * Remove all callbacks for event
     *
     * @param string $event name
     */
    public static function destroy($event)
    {
        unset(self::$events[$event]);
    }
}