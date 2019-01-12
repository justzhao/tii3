<?php
/**
 * EVENT event loop
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
 * @version $Id: Event.php 8923 2017-11-19 11:49:34Z alacner $
 */

class Tii_Worker_Event_Event extends Tii_Worker_Event_Abstract
{
    /**
     * Event base.
     * @var EventBase
     */
    private $base = null;

    public function __construct()
    {
        $this->base = new EventBase();
    }

    public function add($fd, $flag, $func, $args=[])
    {
        switch ($flag) {
            case self::EV_SIGNAL:

                $fd_key = (int)$fd;
                $event = Event::signal($this->base, $fd, $func);
                if (!$event||!$event->add()) {
                    return false;
                }
                $this->signals[$fd_key] = $event;
                return true;

            case self::EV_TIMER:
            case self::EV_TIMER_ONCE:

                $param = [$func, $args, $flag, $fd, self::$id];
                $event = new Event($this->base, -1, Event::TIMEOUT|Event::PERSIST, [$this, "timerCallback"], $param);
                if (!$event||!$event->addTimer($fd)) {
                    return false;
                }
                $this->timers[self::$id] = $event;
                return self::$id++;

            default :
                $fd_key = (int)$fd;
                $real_flag = $flag === self::EV_READ ? Event::READ | Event::PERSIST : Event::WRITE | Event::PERSIST;
                $event = new Event($this->base, $fd, $real_flag, $func, $fd);
                if (!$event||!$event->add()) {
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
                    $this->events[$fd_key][$flag]->del();
                    unset($this->events[$fd_key][$flag]);
                }
                if (empty($this->events[$fd_key])) {
                    unset($this->events[$fd_key]);
                }
                break;

            case  self::EV_SIGNAL:

                $fd_key = (int)$fd;
                if (isset($this->signals[$fd_key])) {
                    $this->events[$fd_key][$flag]->del();
                    unset($this->signals[$fd_key]);
                }
                break;

            case self::EV_TIMER:
            case self::EV_TIMER_ONCE:
                if (isset($this->timers[$fd])) {
                    $this->timers[$fd]->del();
                    unset($this->timers[$fd]);
                }
                break;
        }
        return true;
    }

    /**
     * Timer callback.
     *
     * @param $fd
     * @param $what
     * @param $param
     */
    public function timerCallback($fd, $what, $param)
    {
        list($func, $args, $flag, $fd, $id) = $param;

        if ($flag === self::EV_TIMER_ONCE) {
            $this->timers[$id]->del();
            unset($this->timers[$id]);
        }

        try {
            call_user_func_array($func, $args);
        } catch (Exception $e) {
            Tii_Logger::debug($e->getMessage());
            exit(250);
        }
    }

    public function clearAllTimer()
    {
        foreach ($this->timers as $event) {
            $event->del();
        }
        $this->timers = [];
    }

    public function loop()
    {
        $this->base->loop();
    }

    /**
     * Destroy loop.
     *
     * @return mixed
     */
    public function destroy()
    {
        foreach ($this->signals as $event) {
            $event->del();
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