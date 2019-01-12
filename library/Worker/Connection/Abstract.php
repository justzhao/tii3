<?php
/**
 * Connection Abstract
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

abstract class Tii_Worker_Connection_Abstract extends Tii_Worker_Connection
{
    /**
     * Socket
     *
     * @var resource
     */
    protected $socket = null;

    /**
     * Remote address.
     *
     * @var string
     */
    protected $remoteAddress = '';

    /**
     * Application layer protocol.
     *
     * @var Tii_Worker_Protocol
     */
    public $protocol = null;

    /**
     * Transport (tcp/udp/unix/ssl).
     *
     * @var string
     */
    public $transport = 'tcp';

    /**
     * Emitted when a socket connection is successfully established.
     *
     * @var callback
     */
    public $onConnect;

    /**
     * Emitted when data is received.
     *
     * @var callback
     */
    public $onMessage;

    /**
     * Emitted when the other end of the socket sends a FIN packet.
     *
     * @var callback
     */
    public $onClose;

    /**
     * Emitted when the send buffer becomes full.
     *
     * @var callback
     */
    public $onBufferFull;

    /**
     * Emitted when the send buffer becomes empty.
     *
     * @var callback
     */
    public $onBufferDrain;

    /**
     * Emitted when an error occurs with connection.
     *
     * @var callback
     */
    public $onError;


    /**
     * Construct.
     *
     * @param resource $socket
     * @param string $remote_address
     */
    public function __construct($socket, $remote_address)
    {
        $this->socket = $socket;
        $this->remoteAddress = $remote_address;
    }

    /**
     * Get remote Address
     *
     * @return string
     */
    public function getRemoteAddress()
    {
        return $this->remoteAddress;
    }

    /**
     * Get remote IP.
     *
     * @return string
     */
    public function getRemoteIp()
    {
        $pos = strrpos($this->remoteAddress, ':');
        if ($pos) {
            return trim(substr($this->remoteAddress, 0, $pos), '[]');
        }
        return '';
    }

    /**
     * Get remote port.
     *
     * @return int
     */
    public function getRemotePort()
    {
        if ($this->remoteAddress) {
            return (int)substr(strrchr($this->remoteAddress, ':'), 1);
        }
        return 0;
    }

    /**
     * Get local IP.
     *
     * @return string
     */
    public function getLocalIp()
    {
        $address = $this->getLocalAddress();
        $pos = strrpos($address, ':');
        if (!$pos) {
            return '';
        }
        return substr($address, 0, $pos);
    }

    /**
     * Get local port.
     *
     * @return int
     */
    public function getLocalPort()
    {
        $address = $this->getLocalAddress();
        $pos = strrpos($address, ':');
        if (!$pos) {
            return 0;
        }
        return (int)substr(strrchr($address, ':'), 1);
    }

    /**
     * Get local address.
     *
     * @return string
     */
    public function getLocalAddress()
    {
        return (string)@stream_socket_get_name($this->socket, false);
    }

    /**
     * Is ipv4.
     *
     * return bool.
     */
    public function isIpV4()
    {
        if ($this->transport === 'unix') {
            return false;
        }
        return strpos($this->getRemoteIp(), ':') === false;
    }

    /**
     * Is ipv6.
     *
     * return bool.
     */
    public function isIpV6()
    {
        if ($this->transport === 'unix') {
            return false;
        }
        return strpos($this->getRemoteIp(), ':') !== false;
    }


    /**
     * Sends data on the connection.
     *
     * @param string $buffer
     * @param bool $raw
     * @return void|boolean
     */
    abstract public function send($buffer, $raw = false);

    /**
     * Close connection.
     *
     * @param $data
     * @return void
     */
    abstract public function close($data = null);
}