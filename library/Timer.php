<?php
/**
 * Timers
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
 * @version $Id: Timer.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_Timer
{
    const MIN_RESOLUTION = 0.001;

    private $running = false;
    private $time;
    private $active = [];
    private $timers;
    private $sequenceId = 0;//increase sequence id

    public function __construct()
    {
        $this->timers = new SplPriorityQueue();
    }

    public function updateTime()
    {
        return $this->time = microtime(true);
    }

    public function getTime()
    {
        return $this->time ?: $this->updateTime();
    }

    private function getScheduled($interval, $time)
    {
        if (is_numeric($interval)) {
            return $interval + $time;
        } else {
            return Tii_Time::nexttime($interval, $time);
        }
    }

    /**
     * @param $callback
     * @param mixed $interval float or crontab expression
     * @param bool $periodic
     * @return string
     * @throws Tii_Exception
     */
    public function add($callback, $interval = self::MIN_RESOLUTION, $periodic = false)
    {
        if (is_numeric($interval)) {
            if ($interval < self::MIN_RESOLUTION) {
                throw new Tii_Exception("interval need to numeric and is greater than %s", self::MIN_RESOLUTION);
            }
        } else if(is_string($interval)) {
            if (!Tii_Time::nexttime($interval)) {
                throw new Tii_Exception("invalid crontab expression error");
            }
        } else {
            throw new Tii_Exception("interval error, numeric or crontab expression");
        }

        if (!is_callable($callback)) {
            throw new Tii_Exception("The callback must be a callable object");
        }

        $timer = (object) [
            'interval' => $interval,
            'callback' => $callback,
            'periodic' => $periodic,
            'scheduled' => $this->getScheduled($interval, $this->getTime()),
        ];

        $timer->signature = spl_object_hash($timer);
        $this->timers->insert($timer, -$timer->scheduled);
        $this->active[$timer->signature] = $timer;
        return $timer->signature;
    }

    public function addPeriodic($callback, $interval = self::MIN_RESOLUTION)
    {
        return $this->add($callback, $interval, true);
    }

    public function cancel($signature)
    {
        unset($this->active[$signature]);
    }

    public function getFirst()
    {
        if ($this->timers->isEmpty()) {
            return NULL;
        }
        return $this->timers->top()->scheduled;
    }

    public function isEmpty()
    {
        return !$this->active;
    }

    public function tick()
    {
        $time = $this->updateTime();
        $timers = $this->timers;
        while (!$timers->isEmpty() && $timers->top()->scheduled < $time) {
            $timer = $timers->extract();
            if (isset($this->active[$timer->signature])) {
                call_user_func($timer->callback, $timer->signature, ++$this->sequenceId);
                if ($timer->periodic === true) {
                    $timer->scheduled =  $this->getScheduled($timer->interval, $time);
                    $timers->insert($timer, -$timer->scheduled);
                } else {
                    unset($this->active[$timer->signature]);
                }
            }
        }
        return $this->running;
    }

    /**
     * run loop...
     * @param int $timeout
     */
    public function run($timeout = 0)
    {
        $this->start();

        if ($timeout > 0) {
            $that = $this;
            $this->add(function() use ($that) {$that->stop();}, $timeout);
        }

        while ($this->tick()) {
            // NOOP
        }
    }

    public function start()
    {
        $this->running = true;
    }

    public function stop()
    {
        $this->running = false;
    }
}