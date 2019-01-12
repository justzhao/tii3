<?php
/**
 * Worker Abstract
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

abstract class Tii_Worker_Abstract
{
    public $worker; /** @var Tii_Worker */

    public function __construct($name = '', $runtime = [], $options = [], $host = NULL)
    {
        $worker = new Tii_Worker($name, $runtime, $options, $host);

        foreach(Tii::properties('Tii_Worker', '|^on|') as $method) {
            if (method_exists($this, $method)) {
                $worker->{$method} = [$this, $method];
            }
        }

        $this->worker = $worker;
    }

    /**
     * Worker runner
     */
    public static function run()
    {
        $class = get_called_class();
        new $class;
        Tii_Worker::$globalStart || Tii_Worker::run();
    }
}