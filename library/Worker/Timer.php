<?php
/**
 * Worker Timer
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
 * example:
 * Tii_Worker_Timer::add($time_interval, callback, [$arg1, $arg2..]);
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Timer.php 8923 2017-11-19 11:49:34Z alacner $
 */

final class Tii_Worker_Timer
{
    /**
     * Tasks that based on ALARM signal.
     * [
     *   run_time => [[$func, $args, $persistent, time_interval],[$func, $args, $persistent, time_interval],..]],
     *   run_time => [[$func, $args, $persistent, time_interval],[$func, $args, $persistent, time_interval],..]],
     *   ..
     * ]
     *
     * @var array
     */
    private static $tasks = [];

    /**
     * event
     *
     * @var Tii_Worker_Event_Abstract
     */
    private static $event = null;

    /**
     * Init.
     *
     * @param Tii_Worker_Event_Abstract $event
     * @return void
     */
    public static function init($event = null)
    {
        if ($event) {
            self::$event = $event;
        } else {
            pcntl_signal(SIGALRM, ['Tii_Worker_Timer', 'signalHandler'], false);
        }
    }

    /**
     * ALARM signal handler.
     *
     * @return void
     */
    public static function signalHandler()
    {
        if (!self::$event) {
            pcntl_alarm(1);
            self::tick();
        }
    }

    /**
     * Add a timer.
     *
     * @param $time_interval
     * @param $func
     * @param array $args
     * @param bool $persistent
     * @return bool|mixed
     */
    public static function add($time_interval, $func, $args = [], $persistent = true)
    {
        if ($time_interval <= 0) {
            echo new Exception("bad time_interval");
            return false;
        }

        if (!is_callable($func) || !is_array($args)) {
            echo new Exception("not callable or args not array");
            return false;
        }

        if (self::$event) return self::$event->add(
            $time_interval,
            $persistent ? Tii_Worker_Event::EV_TIMER : Tii_Worker_Event::EV_TIMER_ONCE,
            $func,
            $args
        );

        if (empty(self::$tasks)) pcntl_alarm(1);

        $time = time() + $time_interval;
        isset(self::$tasks[$time]) || self::$tasks[$time] = [];
        self::$tasks[$time][] = [$func, $args, $persistent, $time_interval];

        return true;
    }

    /**
     * Tick.
     *
     * @return void
     */
    public static function tick()
    {
        if (empty(self::$tasks)) {
            pcntl_alarm(0);
            return;
        }

        $now = time();
        foreach (self::$tasks as $time => $tasks) {
            if ($now >= $time) {
                foreach ($tasks as $task) {
                    list($func, $args, $persistent, $time_interval) = $task;
                    try {
                        call_user_func_array($func, $args);
                    } catch (Exception $e) {
                        echo $e;
                    }
                    if ($persistent) self::add($time_interval, $func, $args);
                }
                unset(self::$tasks[$time]);
            }
        }
    }

    /**
     * Remove a timer.
     *
     * @param mixed $fd
     * @return bool
     */
    public static function delete($fd)
    {
        if (self::$event) return self::$event->delete($fd, Tii_Worker_Event::EV_TIMER);
        return false;
    }

    /**
     * Remove all timers.
     *
     * @return void
     */
    public static function delAll()
    {
        self::$tasks = [];
        pcntl_alarm(0);
        if (self::$event) self::$event->clearAllTimer();
    }
}