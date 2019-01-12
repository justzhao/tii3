<?php
/**
 * Tcp Connection
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
 * @version $Id: Tcp.php 8923 2017-11-19 11:49:34Z alacner $
 */

class Tii_Worker_Connection_Tcp extends Tii_Worker_Connection_Abstract
{
    /**
     * Read buffer size.
     *
     * @var int
     */
    const READ_BUFFER_SIZE = 65535;

    /**
     * Status initial.
     *
     * @var int
     */
    const STATUS_INITIAL = 0;

    /**
     * Status connecting.
     *
     * @var int
     */
    const STATUS_CONNECTING = 1;

    /**
     * Status connection established.
     *
     * @var int
     */
    const STATUS_ESTABLISHED = 2;

    /**
     * Status closing.
     *
     * @var int
     */
    const STATUS_CLOSING = 4;

    /**
     * Status closed.
     *
     * @var int
     */
    const STATUS_CLOSED = 8;

    /**
     * Connection->id.
     *
     * @var int
     */
    public $id = 0;

    /**
     * Sets the maximum send buffer size for the current connection.
     * OnBufferFull callback will be emited When the send buffer is full.
     *
     * @var int
     */
    public $maxSendBufferSize = 1048576;

    /**
     * Maximum acceptable packet size.
     *
     * @var int
     */
    public $maxPackageSize = 10485760;

    /**
     * Id recorder.
     *
     * @var int
     */
    protected static $idRecorder = 1;

    /**
     * Send buffer.
     *
     * @var string
     */
    protected $sendBuffer = '';

    /**
     * Receive buffer.
     *
     * @var string
     */
    protected $recvBuffer = '';

    /**
     * Current package length.
     *
     * @var int
     */
    protected $currentPackageLength = 0;

    /**
     * Connection status.
     *
     * @var int
     */
    protected $status = self::STATUS_ESTABLISHED;

    /**
     * SSL handshake completed or not.
     *
     * @var bool
     */
    protected $sslHandshakeCompleted = false;

    /**
     * Is paused.
     *
     * @var bool
     */
    protected $isPaused = false;

    /**
     * Bytes read.
     *
     * @var int
     */
    public $bytesRead = 0;

    /**
     * Bytes written.
     *
     * @var int
     */
    public $bytesWritten = 0;

    /**
     * Which worker belong to.
     *
     * @var Worker
     */
    public $worker = null;

    /**
     * All connection instances.
     *
     * @var array
     */
    public static $connections = [];

    /**
     * Construct.
     *
     * @param resource $socket
     * @param string $remote_address
     * @param Tii_Worker $worker
     */
    public function __construct($socket, $remote_address, $worker = NULL)
    {
        parent::__construct($socket, $remote_address);

        self::$statistics['connection_count']++;
        $this->id = self::$idRecorder++;
        stream_set_blocking($this->socket, 0);

        if (function_exists('stream_set_read_buffer')) {// Compatible with hhvm
            stream_set_read_buffer($this->socket, 0);
        }

        Tii_Worker::$events->add($this->socket, Tii_Worker_Event::EV_READ, [$this, 'baseRead']);

        $this->maxSendBufferSize = Tii_Worker::$init['connection']['default_max_send_buffer_size'];
        $this->maxPackageSize = Tii_Worker::$init['connection']['max_package_size'];

        if ($worker) {
            $this->worker = $worker;
            $this->maxSendBufferSize = Tii::valueInArray($worker->connection, 'default_max_send_buffer_size', 1048576);
            $this->maxPackageSize = Tii::valueInArray($worker->connection, 'max_package_size', 1048576);
        }

        static::$connections[$this->id] = $this;
    }

    /**
     * Get status.
     *
     * @param bool $raw
     *
     * @return int
     */
    public function getStatus($raw = true)
    {
        if ($raw) {
            return $this->status;
        }
        return Tii::valueInArray(Tii::constants(__CLASS__, '|^STATUS_|'), $this->status);
    }

    /**
     * Check whether the send buffer will be full.
     *
     * @return void
     */
    protected function checkBufferWillFull()
    {
        if ($this->maxSendBufferSize <= strlen($this->sendBuffer)) {
            $this->onBufferFull();
        }
    }

    /**
     * Whether send buffer is full.
     *
     * @return bool
     */
    protected function bufferIsFull()
    {
        // Buffer has been marked as full but still has data to send then the packet is discarded.
        if ($this->maxSendBufferSize <= strlen($this->sendBuffer)) {
            $this->onError(Tii_Worker::E_SEND_FAIL, 'send buffer full and drop package');
            return true;
        }
        return false;
    }

    public function send($buffer, $raw = false)
    {
        if (in_array($this->status, [self::STATUS_CLOSING, self::STATUS_CLOSED])) {
            return false;
        }

        // Try to call protocol->encode($buffer) before sending.
        if (false === $raw && $this->protocol) {
            $buffer = $this->protocol->encode($buffer, $this);
            if ($buffer === '') {
                return null;
            }
        }

        if ($this->status !== self::STATUS_ESTABLISHED || ($this->transport === 'ssl' && $this->sslHandshakeCompleted !== true)) {
            if ($this->sendBuffer) {
                if ($this->bufferIsFull()) {
                    self::$statistics['send_fail']++;
                    return false;
                }
            }
            $this->sendBuffer .= $buffer;
            $this->checkBufferWillFull();
            return null;
        }

        // Attempt to send data directly.
        if ($this->sendBuffer === '') {
            $len = @fwrite($this->socket, $buffer, 8192);
            // send successful.
            if ($len === strlen($buffer)) {
                $this->bytesWritten += $len;
                return true;
            }
            // Send only part of the data.
            if ($len > 0) {
                $this->sendBuffer = substr($buffer, $len);
                $this->bytesWritten += $len;
            } else {
                // Connection closed?
                if (!is_resource($this->socket) || feof($this->socket)) {
                    self::$statistics['send_fail']++;
                    $this->onError(Tii_Worker::E_SEND_FAIL, 'client closed');
                    $this->destroy();
                    return false;
                }
                $this->sendBuffer = $buffer;
            }
            Tii_Worker::$events->add($this->socket, Tii_Worker_Event::EV_WRITE, [$this, 'baseWrite']);
            // Check if the send buffer is full.
            $this->checkBufferIsFull();
            return null;
        } else {
            if ($this->bufferIsFull()) {
                self::$statistics['send_fail']++;
                return false;
            }
            $this->sendBuffer .= $buffer;
            // Check if the send buffer is full.
            $this->checkBufferIsFull();
        }
    }

    /**
     * Get send buffer queue size.
     *
     * @return integer
     */
    public function getSendBufferQueueSize()
    {
        return strlen($this->sendBuffer);
    }

    /**
     * Get recv buffer queue size.
     *
     * @return integer
     */
    public function getRecvBufferQueueSize()
    {
        return strlen($this->recvBuffer);
    }

    /**
     * Pauses the reading of data. That is onMessage will not be emitted. Useful to throttle back an upload.
     *
     * @return void
     */
    public function pauseRecv()
    {
        Tii_Worker::$events->delete($this->socket, Tii_Worker_Event::EV_READ);
        $this->isPaused = true;
    }

    /**
     * Resumes reading after a call to pauseRecv.
     *
     * @return void
     */
    public function resumeRecv()
    {
        if ($this->isPaused === true) {
            Tii_Worker::$events->add($this->socket, Tii_Worker_Event::EV_READ, [$this, 'baseRead']);
            $this->isPaused = false;
            $this->baseRead($this->socket, false);
        }
    }

    /**
     * Base read handler.
     *
     * @param resource $socket
     * @param bool $check_eof
     * @return void
     */
    public function baseRead($socket, $check_eof = true)
    {
        // SSL handshake.
        if ($this->transport === 'ssl' && $this->sslHandshakeCompleted !== true) {
            $ret = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_SSLv2_SERVER |
                STREAM_CRYPTO_METHOD_SSLv3_SERVER | STREAM_CRYPTO_METHOD_SSLv23_SERVER);
            // Negotiation has failed.
            if (false === $ret) {
                if (!feof($socket)) {
                    echo "\nSSL Handshake fail. \nBuffer:".bin2hex(fread($socket, 8182))."\n";
                }
                return $this->destroy();
            } elseif (0 === $ret) {
                // There isn't enough data and should try again.
                return;
            }

            $this->onSslHandshake();

            $this->sslHandshakeCompleted = true;
            if ($this->sendBuffer) {
                Tii_Worker::$events->add($socket, Tii_Worker_Event::EV_WRITE, [$this, 'baseWrite']);
            }
            return;
        }

        $buffer = fread($socket, self::READ_BUFFER_SIZE);

        // Check connection closed.
        if ($buffer === '' || $buffer === false) {
            if ($check_eof && (feof($socket) || !is_resource($socket) || $buffer === false)) {
                $this->destroy();
                return;
            }
        } else {
            $this->bytesRead += strlen($buffer);
            $this->recvBuffer .= $buffer;
        }

        // If the application layer protocol has been set up.
        if ($this->protocol) {

            while ($this->recvBuffer !== '' && !$this->isPaused) {
                // The current packet length is known.
                if ($this->currentPackageLength) {
                    // Data is not enough for a package.
                    if ($this->currentPackageLength > strlen($this->recvBuffer)) {
                        break;
                    }
                } else {
                    // Get current package length.
                    $this->currentPackageLength = call_user_func_array($this->protocol->input, [$this->recvBuffer, $this]);
                    // The packet length is unknown.
                    if ($this->currentPackageLength === 0) {
                        break;
                    } elseif ($this->currentPackageLength > 0 && $this->currentPackageLength <= $this->maxPackageSize) {
                        // Data is not enough for a package.
                        if ($this->currentPackageLength > strlen($this->recvBuffer)) {
                            break;
                        }
                    } // Wrong package.
                    else {
                        echo 'error package. package_length=' . var_export($this->currentPackageLength, true);
                        $this->destroy();
                        return;
                    }
                }

                // The data is enough for a packet.
                self::$statistics['total_request']++;
                // The current packet length is equal to the length of the buffer.
                if (strlen($this->recvBuffer) === $this->currentPackageLength) {
                    $one_request_buffer = $this->recvBuffer;
                    $this->recvBuffer  = '';
                } else {
                    // Get a full package from the buffer.
                    $one_request_buffer = substr($this->recvBuffer, 0, $this->currentPackageLength);
                    // Remove the current package from the receive buffer.
                    $this->recvBuffer = substr($this->recvBuffer, $this->currentPackageLength);
                }
                // Reset the current packet length to 0.
                $this->currentPackageLength = 0;

                $this->onMessage($this->protocol->decode($one_request_buffer, $this));
            }
            return;
        }

        if ($this->recvBuffer === '' || $this->isPaused) {
            return;
        }

        // Applications protocol is not set.
        self::$statistics['total_request']++;
        if (!$this->onMessage) {
            $this->recvBuffer = '';
            return;
        }
        $this->onMessage($this->recvBuffer);
        // Clean receive buffer.
        $this->recvBuffer = '';
    }

    /**
     * Base write handler.
     *
     * @return void|bool
     */
    public function baseWrite()
    {
        $len = @fwrite($this->socket, $this->sendBuffer, 8192);
        if ($len === strlen($this->sendBuffer)) {
            Tii_Worker::$events->delete($this->socket, Tii_Worker_Event::EV_WRITE);
            $this->sendBuffer = '';
            // Try to emit onBufferDrain callback when the send buffer becomes empty.
            $this->onBufferDrain();
            if ($this->status === self::STATUS_CLOSING) {
                $this->destroy();
            }
            return true;
        }

        if ($len > 0) {
            $this->bytesWritten += $len;
            $this->sendBuffer = substr($this->sendBuffer, $len);
        } else {
            self::$statistics['send_fail']++;
            $this->destroy();
        }
    }

    /**
     * This method pulls all the data out of a readable stream, and writes it to the supplied destination.
     *
     * @param TcpConnection $dest
     * @return void
     */
    public function pipe($dest)
    {
        $source = $this;
        $this->onMessage = function ($source, $data) use ($dest) {
            $dest->send($data);
        };
        $this->onClose = function ($source) use ($dest) {
            $dest->destroy();
        };
        $dest->onBufferFull = function ($dest) use ($source) {
            $source->pauseRecv();
        };
        $dest->onBufferDrain = function ($dest) use ($source) {
            $source->resumeRecv();
        };
    }

    /**
     * Remove $length of data from receive buffer.
     *
     * @param int $length
     * @return void
     */
    public function consumeRecvBuffer($length)
    {
        $this->recvBuffer = substr($this->recvBuffer, $length);
    }

    public function close($data = null, $raw = false)
    {
        if (in_array($this->status, [self::STATUS_CLOSING, self::STATUS_CLOSED])) {
            return;
        } else {
            if ($data !== null) $this->send($data, $raw);
            $this->status = self::STATUS_CLOSING;
        }
        if ($this->sendBuffer === '') $this->destroy();
    }

    /**
     * Get the real socket.
     *
     * @return resource
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Check whether the send buffer is full.
     *
     * @return void
     */
    protected function checkBufferIsFull()
    {
        if ($this->maxSendBufferSize <= strlen($this->sendBuffer)) {
            $this->onBufferFull();
        }
    }

    /**
     * Destroy connection.
     *
     * @return void
     */
    public function destroy()
    {
        if ($this->status === self::STATUS_CLOSED) return;// Avoid repeated calls.
        // Remove event listener.
        Tii_Worker::$events->delete($this->socket, Tii_Worker_Event::EV_READ);
        Tii_Worker::$events->delete($this->socket, Tii_Worker_Event::EV_WRITE);
        @fclose($this->socket);// Close socket.

        if ($this->worker) {// Remove from worker->connections.
            unset($this->worker->connections[$this->id]);
        }

        unset(static::$connections[$this->id]);
        $this->status = self::STATUS_CLOSED;

        $this->onClose();
        if ($this->protocol) $this->protocol->onClose($this);

        if ($this->status === self::STATUS_CLOSED) {
            // Cleaning up the callback to avoid memory leaks.
            $this->onMessage = $this->onClose = $this->onError = $this->onBufferFull = $this->onBufferDrain = null;
        }
    }

    /**
     * Destruct.
     *
     * @return void
     */
    public function __destruct()
    {
        self::$statistics['connection_count']--;
    }
}