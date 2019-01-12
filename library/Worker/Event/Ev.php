<?php
/**
 * EV event loop
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
 * @version $Id: Ev.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Worker_Event_Ev extends Tii_Worker_Event_Abstract
{
    public function add($fd, $flag, $func, $args = [])
    {
        $callback = function ($event, $socket) use ($fd, $func) {
            try {
                call_user_func($func, $fd);
            } catch (Exception $e) {
                Tii_Logger::debug($e->getMessage());
                exit(250);
            }
        };

        switch ($flag) {
            case self::EV_SIGNAL:
                $this->signals[$fd] = new EvSignal($fd, $callback);
                return true;
            case self::EV_TIMER:
            case self::EV_TIMER_ONCE:
                $repeat = $flag == self::EV_TIMER_ONCE ? 0 : $fd;
                $param = [$func, $args, $flag, $fd, self::$id];
                $this->timers[self::$id] = new EvTimer($fd, $repeat, [$this, 'timerCallback'], $param);
                return self::$id++;
            default :
                $fd_key = (int)$fd;
                $real_flag = $flag === self::EV_READ ? Ev::READ : Ev::WRITE;
                $event = new EvIo($fd, $real_flag, $callback);
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
                    $this->events[$fd_key][$flag]->stop();
                    unset($this->events[$fd_key][$flag]);
                }
                if (empty($this->events[$fd_key])) {
                    unset($this->events[$fd_key]);
                }
                break;
            case  self::EV_SIGNAL:
                $fd_key = (int)$fd;
                if (isset($this->signals[$fd_key])) {
                    $this->events[$fd_key][$flag]->stop();
                    unset($this->signals[$fd_key]);
                }
                break;
            case self::EV_TIMER:
            case self::EV_TIMER_ONCE:
                if (isset($this->timers[$fd])) {
                    $this->timers[$fd]->stop();
                    unset($this->timers[$fd]);
                }
                break;
        }
        return true;
    }

    /**
     * Timer callback.
     *
     * @param EvWatcher $event
     */
    public function timerCallback($event)
    {
        list($func, $args, $flag, $fd, $id) = $event->data;
        if ($flag === self::EV_TIMER_ONCE) {
            $this->timers[$id]->stop();
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
        foreach ($this->timers as $timer) {
            $timer->stop();
        }
        $this->timers = [];
    }

    public function loop()
    {
        Ev::run();
    }

    /**
     * Destroy loop.
     *
     * @return mixed
     */
    public function destroy()
    {
        foreach ($this->events as $event) {
            $event->stop();
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