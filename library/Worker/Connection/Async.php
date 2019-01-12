<?php
/**
 * Async Tcp Connection
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
 * @version $Id: Async.php 8923 2017-11-19 11:49:34Z alacner $
 */

class Tii_Worker_Connection_Async extends Tii_Worker_Connection_Tcp
{
    /**
     * Status.
     *
     * @var int
     */
    protected $status = self::STATUS_INITIAL;

    /**
     * Remote host
     *
     * @var string
     */
    protected $remoteHost = '';

    /**
     * Remote URI
     *
     * @var string
     */
    protected $remoteURI = '';

    /**
     * Connect start time
     *
     * @var string
     */
    protected $connectStartTime = 0;

    /**
     * @var array
     */
    protected $runtime = [];

    /**
     * @param $name
     * @return callable
     */
    public function __get($name)
    {
        return Tii::valueInArray($this->runtime, $name);
    }

    public function getProtocols()
    {
        return Tii_Event::filter('tii.worker.protocols', Tii::valueInArray($this->runtime, 'protocols', []));
    }

    /**
     * Construct.
     *
     * @param string $remote_address
     * @param array $runtime
     * @throws Exception
     */
    public function __construct($remote_address, $runtime = [])
    {
        $addr = parse_url($remote_address);

        if (!isset($addr['host'])) {
            throw new Tii_Exception("bad remote_address: `%s'", $remote_address);
        } else {
            $this->remoteAddress = sprintf("%s:%s", $addr['host'], Tii::valueInArray($addr, 'port', 80));
            $this->remoteHost = $addr['host'];
            $this->remoteURI = Tii_Http::urlAppend(
                Tii::valueInArray($addr, 'path', '/'),
                Tii::valueInArray($addr, 'query')
            );
            $scheme = Tii::valueInArray($addr, 'scheme', 'tcp');
        }

        $this->runtime = array_replace_recursive(Tii_Worker::$init, Tii::get('tii.worker', []), $runtime);
        // Check application layer protocol class.
        if (!isset($this->builtin_transports[$scheme])) {
            $protocols = $this->getProtocols();
            if (!isset($protocols[$scheme])) {
                throw new Tii_Exception("Protocol %s not exist", $scheme);
            }
            $this->protocol = new Tii_Worker_Protocol($protocols[$scheme]);
        } else {
            $this->transport = $this->builtin_transports[$scheme];
        }

        $this->id = self::$idRecorder++;
        // For statistics.
        self::$statistics['connection_count']++;
        $this->maxSendBufferSize = Tii::valueInArray($this->connection, 'default_max_send_buffer_size', 1048576);
    }

    /**
     * Do connect.
     *
     * @return void
     */
    public function connect()
    {
        if (!in_array($this->status, [self::STATUS_INITIAL, self::STATUS_CLOSING, self::STATUS_CLOSED])) {
            return;
        }
        $this->status = self::STATUS_CONNECTING;
        $this->connectStartTime = microtime(true);
        // Open socket connection asynchronously.
        $this->socket = stream_socket_client("{$this->transport}://{$this->remoteAddress}", $errno, $errstr, 0,
            STREAM_CLIENT_ASYNC_CONNECT);
        // If failed attempt to emit onError callback.
        if (!$this->socket) {
            $this->emitError(WORKERMAN_CONNECT_FAIL, $errstr);
            if ($this->status === self::STATUS_CLOSING) {
                $this->destroy();
            }
            if ($this->status === self::STATUS_CLOSED) {
                $this->onConnect = null;
            }
            return;
        }
        // Add socket to global event loop waiting connection is successfully established or faild.
        Tii_Worker::$events->add($this->socket, Tii_Worker_Event::EV_WRITE, [$this, 'checkConnection']);
    }

    /**
     * Get remote address
     *
     * @return string
     */
    public function getRemoteHost()
    {
        return $this->remoteHost;
    }

    /**
     * Get remote URI
     *
     * @return string
     */
    public function getRemoteURI()
    {
        return $this->remoteURI;
    }

    /**
     * Try to emit onError callback.
     *
     * @param int    $code
     * @param string $msg
     * @return void
     */
    protected function emitError($code, $msg)
    {
        $this->status = self::STATUS_CLOSING;
        $this->onError($code, $msg);
    }

    /**
     * Check connection is successfully established or failed.
     *
     * @param resource $socket
     * @return void
     */
    public function checkConnection($socket)
    {
        // Check socket state.
        if ($address = stream_socket_get_name($socket, true)) {
            // Remove write listener.
            Tii_Worker::$events->delete($socket, Tii_Worker_Event::EV_WRITE);
            // Nonblocking.
            stream_set_blocking($socket, 0);
            // Compatible with hhvm
            if (function_exists('stream_set_read_buffer')) {
                stream_set_read_buffer($socket, 0);
            }
            // Try to open keepalive for tcp and disable Nagle algorithm.
            if (function_exists('socket_import_stream') && $this->transport === 'tcp') {
                $rawsocket = socket_import_stream($socket);
                socket_set_option($rawsocket, SOL_SOCKET, SO_KEEPALIVE, 1);
                socket_set_option($rawsocket, SOL_TCP, TCP_NODELAY, 1);
            }
            // Register a listener waiting read event.
            Tii_Worker::$events->add($socket, Tii_Worker_Event::EV_READ, [$this, 'baseRead']);
            // There are some data waiting to send.
            if ($this->sendBuffer) {
                Tii_Worker::$events->add($socket, Tii_Worker_Event::EV_WRITE, [$this, 'baseWrite']);
            }
            $this->status = self::STATUS_ESTABLISHED;
            $this->remoteAddress = $address;
            $this->sslHandshakeCompleted = true;

            // Try to emit onConnect callback.
            $this->onConnect();
            if ($this->protocol) $this->protocol->onConnect($this);

        } else {
            // Connection failed.
            $this->emitError(Tii_Worker::E_CONNECT_FAIL, 'connect ' . $this->remoteAddress . ' fail after ' . round(microtime(true) - $this->connectStartTime, 4) . ' seconds');
            if ($this->status === self::STATUS_CLOSING) {
                $this->destroy();
            }
            if ($this->status === self::STATUS_CLOSED) {
                $this->onConnect = null;
            }
        }
    }
}