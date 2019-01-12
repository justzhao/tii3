<?php
/**
 * Select event loop
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
 * @version $Id: Select.php 8923 2017-11-19 11:49:34Z alacner $
 */

class Tii_Worker_Event_Select extends Tii_Worker_Event_Abstract
{
    /**
     * Use pcntl signal
     *
     * @var bool
     */
    private $usePcntlSignal = true;

    /**
     * Fds waiting for read event.
     *
     * @var array
     */
    private $read = [];

    /**
     * Fds waiting for write event.
     *
     * @var array
     */
    private $write = [];

    /**
     * Timer scheduler.
     * {['data':timer_id, 'priority':run_timestamp], ..}
     *
     * @var SplPriorityQueue
     */
    private $scheduler = null;

    /**
     * Select timeout.
     *
     * @var int
     */
    private $timeout = 100000000;

    /**
     * Paired socket channels
     *
     * @var array
     */
    private $channel = [];

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->usePcntlSignal = function_exists('pcntl_signal');
        // Create a pipeline and put into the collection of the read to read the descriptor to avoid empty polling.
        $this->channel = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($this->channel) {
            stream_set_blocking($this->channel[0], 0);
            $this->read[0] = $this->channel[0];
        }
        // Init SplPriorityQueue.
        $this->scheduler = new SplPriorityQueue();
        $this->scheduler->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
    }

    public function add($fd, $flag, $func, $args = [])
    {
        switch ($flag) {
            case self::EV_READ:
                $fd_key = (int)$fd;
                $this->events[$fd_key][$flag] = [$func, $fd];
                $this->read[$fd_key] = $fd;
                break;
            case self::EV_WRITE:
                $fd_key = (int)$fd;
                $this->events[$fd_key][$flag] = [$func, $fd];
                $this->write[$fd_key] = $fd;
                break;
            case self::EV_SIGNAL:
                if (!$this->usePcntlSignal) return false;
                $fd_key = (int)$fd;
                $this->signals[$fd_key][$flag] = [$func, $fd];
                pcntl_signal($fd, [$this, 'signalHandler']);
                break;
            case self::EV_TIMER:
            case self::EV_TIMER_ONCE:
                $run_time = microtime(true) + $fd;
                $this->scheduler->insert(self::$id, -$run_time);
                $this->timers[self::$id] = [$func, $args, $flag, $fd];
                $this->tick();
                return self::$id++;
        }

        return true;
    }

    /**
     * Signal handler.
     *
     * @param int $signal
     */
    public function signalHandler($signal)
    {
        call_user_func_array($this->signals[$signal][self::EV_SIGNAL][0], [$signal]);
    }

    public function delete($fd, $flag)
    {
        $fd_key = (int)$fd;
        switch ($flag) {
            case self::EV_READ:
                unset($this->events[$fd_key][$flag], $this->read[$fd_key]);
                if (empty($this->events[$fd_key])) {
                    unset($this->events[$fd_key]);
                }
                return true;
            case self::EV_WRITE:
                unset($this->events[$fd_key][$flag], $this->write[$fd_key]);
                if (empty($this->events[$fd_key])) {
                    unset($this->events[$fd_key]);
                }
                return true;
            case self::EV_SIGNAL:
                if (!$this->usePcntlSignal) return false;
                unset($this->signals[$fd_key]);
                pcntl_signal($fd, SIG_IGN);
                break;
            case self::EV_TIMER:
            case self::EV_TIMER_ONCE;
                unset($this->timers[$fd_key]);
                return true;
        }
        return false;
    }

    /**
     * Tick for timer.
     *
     * @return void
     */
    protected function tick()
    {
        while (!$this->scheduler->isEmpty()) {
            $scheduler_data = $this->scheduler->top();
            $timer_id = $scheduler_data['data'];
            $next_run_time = -$scheduler_data['priority'];
            $time_now = microtime(true);
            $this->timeout = ($next_run_time - $time_now) * 1000000;
            if ($this->timeout <= 0) {
                $this->scheduler->extract();

                if (!isset($this->timers[$timer_id])) continue;

                list($func, $args, $flag, $fd) = $this->timers[$timer_id];

                if ($flag === self::EV_TIMER) {
                    $next_run_time = $time_now + $fd;
                    $this->scheduler->insert($timer_id, -$next_run_time);
                }
                call_user_func_array($func, $args);
                if (isset($this->timers[$timer_id]) && $flag === self::EV_TIMER_ONCE) {
                    $this->delete($timer_id, self::EV_TIMER_ONCE);
                }
                continue;
            }
            return;
        }
        $this->timeout = 100000000;
    }

    public function clearAllTimer()
    {
        $this->scheduler = new SplPriorityQueue();
        $this->scheduler->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
        $this->timers = [];
    }

    public function loop()
    {
        $e = null;
        while (1) {
            if ($this->usePcntlSignal) {
                // Calls signal handlers for pending signals
                pcntl_signal_dispatch();
            }

            $read  = $this->read;
            $write = $this->write;
            // Waiting read/write/signal/timeout events.
            $ret = @stream_select($read, $write, $e, 0, $this->timeout);
            if (!$this->scheduler->isEmpty()) $this->tick();
            if (!$ret) continue;

            foreach ($read as $fd) {
                $fd_key = (int)$fd;
                if (isset($this->events[$fd_key][self::EV_READ])) {
                    list($func, $args) = $this->events[$fd_key][self::EV_READ];
                    call_user_func_array($func, [$args]);
                }
            }

            foreach ($write as $fd) {
                $fd_key = (int)$fd;
                if (isset($this->events[$fd_key][self::EV_WRITE])) {
                    list($func, $args) = $this->events[$fd_key][self::EV_WRITE];
                    call_user_func_array($func, [$args]);
                }
            }
        }
    }

    /**
     * Destroy loop.
     *
     * @return mixed
     */
    public function destroy()
    {
        //ignore
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