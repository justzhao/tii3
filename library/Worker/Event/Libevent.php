<?php
/**
 * Libevent event loop
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
 * @version $Id: Libevent.php 8923 2017-11-19 11:49:34Z alacner $
 */

class Tii_Worker_Event_Libevent extends Tii_Worker_Event_Abstract
{
    /**
     * Event base.
     *
     * @var resource
     */
    private $base = null;

    /**
     * construct
     */
    public function __construct()
    {
        $this->base = event_base_new();
    }

    public function add($fd, $flag, $func, $args = [])
    {
        switch ($flag) {
            case self::EV_SIGNAL:
                $fd_key = (int)$fd;
                $this->signals[$fd_key] = event_new();
                if (!event_set($this->signals[$fd_key], $fd, EV_SIGNAL | EV_PERSIST, $func, null)) {
                    return false;
                }
                if (!event_base_set($this->signals[$fd_key], $this->base)) {
                    return false;
                }
                if (!event_add($this->signals[$fd_key])) {
                    return false;
                }
                return true;
            case self::EV_TIMER:
            case self::EV_TIMER_ONCE:
                $event = event_new();
                $timer_id = (int)$event;
                if (!event_set($event, 0, EV_TIMEOUT, [$this, 'timerCallback'], $timer_id)) {
                    return false;
                }

                if (!event_base_set($event, $this->base)) {
                    return false;
                }

                $time_interval = $fd * 1000000;
                if (!event_add($event, $time_interval)) {
                    return false;
                }
                $this->timers[$timer_id] = [$func, $args, $flag, $event, $time_interval];
                return $timer_id;

            default :
                $fd_key    = (int)$fd;
                $real_flag = $flag === self::EV_READ ? EV_READ | EV_PERSIST : EV_WRITE | EV_PERSIST;

                $event = event_new();

                if (!event_set($event, $fd, $real_flag, $func, null)) {
                    return false;
                }

                if (!event_base_set($event, $this->base)) {
                    return false;
                }

                if (!event_add($event)) {
                    return false;
                }

                $this->events[$fd_key][$flag] = $event;

                return true;
        }

    }

    public function delete($fd, $flag)
    {
        switch ($flag) {
            case self::EV_READ:
            case self::EV_WRITE:
                $fd_key = (int)$fd;
                if (isset($this->events[$fd_key][$flag])) {
                    event_del($this->events[$fd_key][$flag]);
                    unset($this->events[$fd_key][$flag]);
                }
                if (empty($this->events[$fd_key])) {
                    unset($this->events[$fd_key]);
                }
                break;
            case  self::EV_SIGNAL:
                $fd_key = (int)$fd;
                if (isset($this->signals[$fd_key])) {
                    event_del($this->signals[$fd_key]);
                    unset($this->signals[$fd_key]);
                }
                break;
            case self::EV_TIMER:
            case self::EV_TIMER_ONCE:
                // fd is timerid
                if (isset($this->timers[$fd])) {
                    event_del($this->timers[$fd][3]);//3 => $event
                    unset($this->timers[$fd]);
                }
                break;
        }
        return true;
    }

    /**
     * Timer callback.
     *
     * @param mixed $_null1
     * @param int   $_null2
     * @param mixed $timer_id
     */
    protected function timerCallback($_null1, $_null2, $timer_id)
    {
        list($func, $args, $flag, $event, $time_interval) = $this->timers[$timer_id];

        if ($flag === self::EV_TIMER) {
            event_add($event, $time_interval);
        }
        try {
            call_user_func_array($func, $args);
        } catch (Exception $e) {
            Tii_Logger::debug($e->getMessage());
            exit(250);
        }
        if (isset($this->timers[$timer_id]) && $flag === self::EV_TIMER_ONCE) {
            $this->del($timer_id, self::EV_TIMER_ONCE);
        }
    }

    public function clearAllTimer()
    {
        foreach ($this->timers as $task_data) {
            event_del($task_data[3]);//3 => $event
        }
        $this->timers = [];
    }

    public function loop()
    {
        event_base_loop($this->base);
    }

    /**
     * Destroy loop.
     *
     * @return mixed
     */
    public function destroy()
    {
        foreach ($this->signals as $event) {
            event_del($event);
        }
    }

    /**
     * Get Timer count.
     *
     * @return mixed
     */
    public function getTimerCount()
    {
        return count($this->timers);
    }
}