<?php
/**
 * Worker Client
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
 * Usage:
 *
 * $client = new Tii_Worker_Client('text.json://127.0.0.1:4521');
 * $client->request([...])
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Client.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Worker_Client
{
    public static $clients = [];

    public $id;
    protected $socket;
    protected $remote_socket;
    protected $timeout;
    protected $protocol;
    protected $type = 'text';

    public $onConnect;
    public $onMessage;
    public $onClose;
    public $onError;

    /**
     * send by dup protocol
     *
     * @param $address
     * @param $buffer
     * @param $throw
     * @return bool
     * @throws Tii_Exception
     */
    public static function broadcast($address, $buffer, $throw = false)
    {
        $socket = stream_socket_client($address, $errno, $errstr);
        if (!$socket) {
            if ($throw) throw new Tii_Exception($errstr);
            Tii_Logger::debug("broadcast $address error: $errstr", func_num_args());
            return false;
        }
        return stream_socket_sendto($socket, $buffer) == strlen($buffer);
    }

    public function __construct($remote_address, $timeout = 5)
    {
        // Save all worker instances.
        $this->id = spl_object_hash($this);
        self::$clients[$this->id] = $this;

        list($scheme, $address) = explode(':', $remote_address, 2);
        // Check application layer protocol class.
        $builtin_transports = Tii_Worker::$init['builtin_transports'];

        $transport = 'tcp';
        if (!isset($builtin_transports[$scheme])) {
            $protocols = $this->getProtocols();
            if (!isset($protocols[$scheme])) {
                throw new Exception("Protocol `$scheme' not exist");
            }
            $this->protocol = new Tii_Worker_Protocol($protocols[$scheme]);
            list($this->type) = explode('.', $scheme, 2);
        } else {
            $transport = $builtin_transports[$scheme];
        }

        $this->remote_socket = "{$transport}:{$address}";
        $this->timeout = $timeout ?: ini_get("default_socket_timeout");
    }

    protected function getProtocols()
    {
        static $protocols;
        $protocols || $protocols = Tii_Event::filter('tii.worker.protocols',
            Tii::valueInArray(
                array_replace_recursive(Tii_Worker::$init, Tii::get('tii.worker', [])),
                'protocols',
                []
            )
        );
        return $protocols;
    }

    public function assert()
    {
        $socket = stream_socket_client($this->remote_socket, $errno, $errstr, $this->timeout);
        if (!$socket) {
            throw new Tii_Exception($errstr);
        }
        $this->socket = $socket;
        return true;
    }

    public function getSocket()
    {
        is_resource($this->socket) || $this->connect();
        return $this->socket;
    }

    public function connect()
    {
        $this->assert();
        stream_set_blocking($this->socket, true);
        list($sec, $usec) = explode('.', $this->timeout . '.');
        stream_set_timeout($this->socket, intval($sec), intval($usec));
        if ($this->onConnect) call_user_func($this->onConnect, $this, $this->receive());
    }

    /**
     * Start Listening...
     *
     * @param int $timeout
     * @param $func
     */
    public static function run($timeout = 0, $func = NULL)
    {
        $timer = new Tii_Timer();

        $timer->addPeriodic(function()
        {
            foreach(self::$clients as $id => $client) {
                try {
                    while(!in_array(($message = $client->receive()), ['', NULL])) {
                        if ($client->onMessage) call_user_func($client->onMessage, $client, $message);
                    }
                } catch (Exception $e) {
                    if ($client->onError) call_user_func($client->onError, $client, $e->getMessage());
                }
            }
        });

        $timer->addPeriodic(function() {sleep(1);});

        if (is_callable($func)) call_user_func($func, $timer);

        $timer->run($timeout);
    }

    /**
     * Request the client
     *
     * @param string $buffer
     * @return mixed
     * @throws Tii_Exception
     */
    public function request($buffer = '')
    {
        if (!$this->send($buffer)) throw new Tii_Exception('send via [%s] chunk failed', $this->remote_socket);
        return $this->receive();
    }

    /**
     * Receive data
     *
     * @return mixed
     */
    public function receive()
    {
        return call_user_func([$this, $this->type]);
    }

    protected function send($buffer, $tries = 3, $encoded = false)
    {
        if ($this->protocol && !$encoded) {
            $buffer = $this->protocol->encode($buffer);
        }

        if (fwrite($this->getSocket(), $buffer) === strlen($buffer)) {
            return true;
        } else {
            $this->connect();
            if ($tries) return $this->send($buffer, --$tries, true);
            return false;
        }
    }

    protected function chunk($tries = 3, $total_len = 4, $length = 8192)
    {
        $all_buffer = '';
        $head_read = false;
        $socket = $this->getSocket();

        while(1) {
            $buffer = fread($socket, $length);
            if ($buffer === '') return NULL;
            if ($buffer === false) {
                $this->connect();
                if ($tries) return $this->chunk(--$tries, $total_len, $length);
                throw new Tii_Exception('failed to get data from %s by chunk', $this->remote_socket);
            }
            $all_buffer .= $buffer;
            $recv_len = strlen($all_buffer);
            if ($recv_len >= $total_len) {
                if ($head_read) break;
                $unpack_data = unpack('Ntotal_length', $all_buffer);
                $total_len = $unpack_data['total_length'];
                if ($recv_len >= $total_len) break;
                $head_read = true;
            }
        }

        if ($this->protocol) {
            $all_buffer = $this->protocol->decode($all_buffer);
        }

        return $all_buffer;
    }

    protected function text($tries = 3, $length = 1024)
    {
        $all_buffer = '';
        $socket = $this->getSocket();

        while (1) {
            $buffer = fgets($socket, 1024);
            if ($buffer === '') return NULL;
            if ($buffer === false) {
                $this->connect();
                if ($tries) return $this->text(--$tries, $length);
                throw new Tii_Exception('failed to get data from %s by text', $this->remote_socket);
            }

            $all_buffer .= $buffer;
            if (strpos($all_buffer, "\n") !== false) {
                break;
            }
        }

        $all_buffer = trim($all_buffer);

        if ($this->protocol) {
            $all_buffer = $this->protocol->decode($all_buffer);
        }

        return $all_buffer;
    }

    public function close()
    {
        if (is_resource($this->socket)) @fclose($this->socket);
        $this->socket = NULL;
        if ($this->onClose) call_user_func($this->onClose);
    }

    public function __destruct()
    {
        $this->close();
    }
}