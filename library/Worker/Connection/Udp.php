<?php
/**
 * Udp Connection
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
 * @version $Id: Udp.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Worker_Connection_Udp extends Tii_Worker_Connection_Abstract
{
    public function send($buffer, $raw = false)
    {
        if (false === $raw && $this->protocol) {
            $buffer = $this->protocol->encode($buffer, $this);
            if ($buffer === '') {
                return null;
            }
        }
        return strlen($buffer) === stream_socket_sendto($this->socket, $buffer, 0, $this->remoteAddress);
    }

    public function close($data = null, $raw = false)
    {
        if ($data !== null) {
            $this->send($data, $raw);
        }
        return true;
    }
}