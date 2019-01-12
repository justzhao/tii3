<?php
/**
 * Event Abstract
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

abstract class Tii_Worker_Event_Abstract extends Tii_Worker_Event
{
    /**
     * All listeners for read/write event.
     *
     * @var array
     */
    protected $events = [];

    /**
     * Event listeners of signal.
     *
     * @var array
     */
    protected $signals = [];

    /**
     * All timer event listeners.
     * [func, args, flag, arg1,arg2,...]
     *
     * @var array
     */
    protected $timers = [];

    /**
     * Timer id.
     *
     * @var int
     */
    protected static $id = 1;

    /**
     * Add event listener to event loop.
     *
     * @param $fd
     * @param $flag
     * @param callable $func
     * @param array $args
     * @return mixed
     */
    abstract public function add($fd, $flag, $func, $args = []);

    /**
     * Remove event listener from event loop.
     *
     * @param mixed $fd
     * @param int $flag
     * @return bool
     */
    abstract public function delete($fd, $flag);

    /**
     * Remove all timers.
     *
     * @return void
     */
    abstract public function clearAllTimer();

    /**
     * Main loop.
     *
     * @return void
     */
    abstract public function loop();

    /**
     * Destroy loop.
     *
     * @return mixed
     */
    abstract public function destroy();

    /**
     * Get Timer count.
     *
     * @return mixed
     */
    abstract public function getTimerCount();
}