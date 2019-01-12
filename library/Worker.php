<?php
/**
 * A container for listening ports
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
 * example:
 * $worker1 = new Tii_Worker('tcp://0.0.0.0:1234');
 * $worker2 = new Tii_Worker('udp://0.0.0.0:2345');
 * Tii_Worker::run();
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Worker.php 8923 2017-11-19 11:49:34Z alacner $
 */

class Tii_Worker extends Tii_Worker_Callable
{
    /**
     * For onError callback.
     *
     * @var int
     */
    const E_CONNECT_FAIL = 1;

    /**
     * For onError callback.
     *
     * @var int
     */
    const E_SEND_FAIL = 2;

    /**
     * Status starting.
     *
     * @var int
     */
    const STATUS_STARTING = 1;

    /**
     * Status running.
     *
     * @var int
     */
    const STATUS_RUNNING = 2;

    /**
     * Status shutdown.
     *
     * @var int
     */
    const STATUS_SHUTDOWN = 4;

    /**
     * Status reloading.
     *
     * @var int
     */
    const STATUS_RELOADING = 8;

    /**
     * Worker id
     *
     * @var int
     */
    public $id = 0;

    /**
     * Store all connections of clients
     *
     * @var array
     */
    public $connections = [];

    /**
     * localhost
     *
     * @var array
     */
    public $host;

    /**
     * The Data Source Name, or DSN, contains the information required to connect
     *
     * @var string
     */
    public $dsn;

    /**
     * Application layer protocol.
     *
     * @var Tii_Worker_Protocol
     */
    public $protocol = null;

    /**
     * Pause accept new connections or not.
     *
     * @var string
     */
    public $pauseAccept = true;

    /**
     * listening socket
     *
     * @var null|resource
     */
    public $socket = null;

    /**
     * Socket name, format like http://0.0.0.0:80
     *
     * @var string
     */
    public $socketName = '';

    /**
     * Context of socket
     *
     * @var null|resource
     */
    public $context = null;

    /**
     * Name of the worker processes.
     *
     * @var string
     */
    public $name = 'none';

    /**
     * Runtime
     *
     * @var array
     */
    protected $runtime = [];

    /**
     * Default init
     *
     * @var array
     */
    public static $init = [
        'count' => 1,//number of worker processes
        'transport' => 'tcp',//transport layer protocol
        'protocol ' => '',//application layer protocol
        'reloadable' => true,
        'reuse_port' => false,
        'graceful_stop' => false,//Graceful stop or not.
        'graceful_time' => 2,//Wait a few seconds, and then kill it before it's over
        'default_backlog' => 1024,//default backlog. Backlog is the maximum length of the queue of pending connections
        'max_udp_package_size' => 65535,//max udp package size
        'name' => 'none',//default name of the worker processes
        'user' => '',//unix user of processes, needs appropriate privileges (usually root)
        'group' => '',//unix group of processes, needs appropriate privileges (usually root)
        'available_events' => [//available event loops
            'libevent',
            'event',
            'ev'
        ],
        'default_event' => 'select',//current eventLoop name
        'builtin_transports' => [
            'tcp' => 'tcp',
            'udp' => 'udp',
            'unix' => 'unix',
            'ssl' => 'tcp',
            'sslv2' => 'tcp',
            'sslv3' => 'tcp',
            'tls' => 'tcp'
        ],
        'connection' => [
            'read_buffer_size' => 65535,//read buffer size
            'max_send_buffer_size' => 1048576,//sets the maximum send buffer size for the current connection
            //onBufferFull callback will be emited When the send buffer is full
            'default_max_send_buffer_size' => 1048576,//default send buffer size
            'max_package_size' => 10485760,//maximum acceptable packet size
        ],
    ];

    /**
     * Start <Tii_Worker_Abstract> workers together
     *
     * @var bool
     */
    public static $globalStart = false;

    /**
     * Start file.
     *
     * @var string
     */
    public static $startFile = '';

    /**
     * Daemonize.
     *
     * @var bool
     */
    public static $daemonize = false;

    /**
     * Global event loop.
     *
     * @var Tii_Worker_Event_Abstract
     */
    public static $events = null;

    /**
     * Emitted when the master process get reload signal.
     *
     * @var callback
     */
    public static $onMasterReload = null;

    /**
     * Emitted when the master process terminated.
     *
     * @var callback
     */
    public static $onMasterStop = null;

    /**
     * Stdout file.
     *
     * @var string
     */
    public static $stdoutFile = '/dev/null';

    /**
     * Current status.
     *
     * @var int
     */
    public static $status = self::STATUS_STARTING;

    /**
     * The PID of master process.
     *
     * @var int
     */
    public static $pid = 0;

    /**
     * All worker porcesses pid.
     * The format is like this [worker_id=>[pid=>pid, pid=>pid, ..], ..]
     *
     * @var array
     */
    public static $pids = [];

    /**
     * Mapping from PID to worker process ID.
     * The format is like this [worker_id=>[0=>$pid, 1=>$pid, ..], ..].
     *
     * @var array
     */
    public static $ids = [];

    /**
     * All worker instances.
     *
     * @var array
     */
    public static $workers = [];

    /**
     * Status info of current worker process.
     *
     * @var array
     */
    public static $statistics = [
        'start_timestamp' => 0,
        'worker_exit_info' => [],
    ];

    /**
     * Emitted when worker processes start.
     *
     * @var callback
     */
    public $onWorkerStart;

    /**
     * Emitted when worker processes get reload signal.
     *
     * @var callback
     */
    public $onWorkerReload;

    /**
     * Emitted when worker processes stoped.
     *
     * @var callback
     */
    public $onWorkerStop;

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

    public function __get($name)
    {
        return Tii::valueInArray($this->runtime, $name);
    }

    /**
     * Construct.
     *
     * @param string $name
     * @param array $runtime
     * @param array $options
     * @param string $host
     */
    public function __construct($name = '', $runtime = [], $options = [], $host = NULL)
    {
        // Save all worker instances.
        $this->id = spl_object_hash($this);
        self::$workers[$this->id] = $this;
        self::$pids[$this->id] = [];

        $this->runtime = array_replace_recursive(static::$init, Tii::get('tii.worker', []), $runtime);
        $this->name = $this->runtime['name'];

        $this->host = $host ?: Tii_Network::getIp();

        // Context for socket.
        if ($name) {
            $this->socketName = $name;
            if (!isset($options['socket']['backlog'])) {
                $options['socket']['backlog'] = Tii::valueInArray($this->runtime, 'default_backlog', 1204);
            }
            $this->context = stream_context_create($options);
            $this->dsn = str_replace('0.0.0.0', $this->host, $name);
        }
    }

    /**
     * Get supported protocols
     *
     * @return array
     */
    public function getProtocols()
    {
        return Tii_Event::filter('tii.worker.protocols', Tii::valueInArray($this->runtime, 'protocols', []));
    }

    /**
     * Pause accept new connections.
     *
     * @return void
     */
    public function pauseAccept()
    {
        // Register a listener to be notified when server socket is ready to read.
        if (static::$events && !$this->pauseAccept && $this->socket) {
            static::$events->delete($this->socket, Tii_Worker_Event::EV_READ);// Remove listener for server socket.
            $this->pauseAccept = true;
        }
    }

    /**
     * Resume accept new connections.
     *
     * @return void
     */
    public function resumeAccept()
    {
        // Register a listener to be notified when server socket is ready to read.
        if (static::$events && $this->pauseAccept && $this->socket) {
            if ($this->transport === 'udp') {
                static::$events->add($this->socket, Tii_Worker_Event::EV_READ, [$this, 'acceptUdpConnection']);
            } else {
                static::$events->add($this->socket, Tii_Worker_Event::EV_READ, [$this, 'acceptConnection']);
            }
            $this->pauseAccept = false;
        }
    }

    /**
     * Loaded together to run
     *
     * @param $name
     */
    public static function runAll($name = NULL)
    {
        static::$globalStart = true;
        if ($name) static::$startFile = $name;

        foreach (Tii::get('tii.worker.loader.classes', []) as $class) {
            call_user_func([$class, 'run']);
        }

        foreach (Tii::get('tii.worker.loader.files', []) as $file) {
            require_once $file;
        }

        static::run();
    }

    /**
     * Listen port.
     *
     * @throws Exception
     */
    public function listen()
    {
        if (!$this->socketName || $this->socket) {
            return;
        }

        $local_socket = $this->socketName;
        // Get the application layer communication protocol and listening address.
        list($scheme, $address) = explode(':', $this->socketName, 2);
        // Check application layer protocol class.
        if (!isset($this->builtin_transports[$scheme])) {
            $protocols = $this->getProtocols();
            if (!isset($protocols[$scheme])) {
                throw new Exception("Protocol $scheme not exist");
            }
            $this->protocol = new Tii_Worker_Protocol($protocols[$scheme]);
            $local_socket = $this->transport . ":" . $address;
        } else {
            $this->transport = $this->builtin_transports[$scheme];
        }

        // Flag.
        $flags = $this->transport === 'udp' ? STREAM_SERVER_BIND : STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
        $errno = 0;
        $errmsg = '';
        if ($this->reuse_port) {// SO_REUSEPORT.
            stream_context_set_option($this->context, 'socket', 'so_reuseport', 1);
        }

        // Create an Internet or Unix domain server socket.
        $this->socket = stream_socket_server($local_socket, $errno, $errmsg, $flags, $this->context);
        if (!$this->socket) {
            throw new Tii_Worker_Exception($errmsg);
        }

        if ($this->transport === 'ssl') {
            stream_socket_enable_crypto($this->socket, false);
        } else if ($this->transport === 'unix') {
            list(, $address) = explode(':', $this->socketName, 2);
            if (!is_file($address)) {
                register_shutdown_function(function () use ($address) {
                    @unlink($address);
                });
            }
            if ($this->user) {
                chown($address, $this->user);
            }
            if ($this->group) {
                chgrp($address, $this->group);
            }
        }

        // Try to open keepalive for tcp and disable Nagle algorithm.
        if (function_exists('socket_import_stream') && $this->transport === 'tcp') {
            $socket = socket_import_stream($this->socket);
            @socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
            @socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1);
        }

        // Non blocking.
        stream_set_blocking($this->socket, 0);

        $this->resumeAccept();
    }

    /**
     * Stop current worker instance.
     *
     * @return void
     */
    public function stop()
    {
        $this->onWorkerStop();// Try to emit onWorkerStop callback.
        // Remove listener for server socket.
        $this->pauseAccept();
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }

        // Close all connections for the worker.
        if (!$this->graceful_stop) {
            foreach ($this->connections as $connection) {
                $connection->close();
            }
        }

        // Clear callback.
        $this->onMessage = $this->onClose = $this->onError = $this->onBufferDrain = $this->onBufferFull = null;
        // Remove worker instance from static::$workers.
        unset(self::$workers[$this->id]);
    }

    /**
     * Run worker instance.
     *
     * @return void
     */
    public function start()
    {
        //Update process state.
        self::$status = self::STATUS_RUNNING;

        // Eegister shutdown function for checking errors.
        //register_shutdown_function(["Tii_Worker", 'checkErrors']);

        // Create a global event loop.
        if (!static::$events) {
            static::$events = Tii_Worker_Event::instance();
            // Register a listener to be notified when server socket is ready to read.
            $this->resumeAccept();
        }

        static::starting();

        // Init Timer.
        Tii_Worker_Timer::init(static::$events);

        // Try to emit onWorkerStart callback.
        $this->onWorkerStart();
        // Main loop.
        static::$events->loop();
    }

    /**
     * Accept a connection.
     *
     * @param resource $socket
     * @return void
     */
    public function acceptConnection($socket)
    {
        $new_socket = @stream_socket_accept($socket, 0, $remote_address);// Accept a connection on server socket.
        if (!$new_socket) return;// Thundering herd.

        $connection = new Tii_Worker_Connection_Tcp($new_socket, $remote_address);
        /** @var Tii_Worker_Connection_Tcp */

        $this->connections[$connection->id] = $connection;
        $connection->worker = $this;
        $connection->protocol = $this->protocol;
        $connection->onConnect = $this->onConnect;
        $connection->onMessage = $this->onMessage;
        $connection->onClose = $this->onClose;
        $connection->onBufferFull = $this->onBufferFull;
        $connection->onBufferDrain = $this->onBufferDrain;
        $connection->onError = $this->onError;

        $connection->onConnect();// Try to emit onConnect callback.
        //if ($this->protocol) $this->protocol->onConnect($connection);
    }

    /**
     * For udp package.
     *
     * @param resource $socket
     * @return bool
     */
    public function acceptUdpConnection($socket)
    {
        $recv = stream_socket_recvfrom($socket, $this->max_udp_package_size, 0, $remote_address);
        if (false === $recv || empty($remote_address)) return false;
        $connection = new Tii_Worker_Connection_Udp($socket, $remote_address);
        /** @var Tii_Worker_Connection_Udp */
        $connection->protocol = $this->protocol;
        $connection->onMessage = $this->onMessage;

        if ($this->onMessage) {
            if ($this->protocol) {
                $recv = $this->protocol->decode($recv, $connection);
                if ($recv === false) return true;// Discard bad packets
            }
            Tii_Worker_Connection::$statistics['total_request']++;
            $connection->onMessage($recv);
        }
        return true;
    }

    /**
     * Init worker
     *
     * return void
     */
    public static function init($event = null)
    {
        $backtrace = debug_backtrace();
        if (!static::$startFile) static::$startFile = $backtrace[count($backtrace) - 1]['file'];//start file.

        self::$status = self::STATUS_STARTING;//state.
        self::$statistics['start_timestamp'] = time();
        Tii_Worker::setProcessTitle('Worker: master process  start_file=' . static::$startFile);//process title.

        Tii::usage();

        if (Tii::get('tii.timezone')) {
            Tii_Time::timezone(Tii::get('tii.timezone'));
        }

        //Init event
        Tii_Event::init();

        //Init data for worker id.
        self::initIds();

        //Init worker timer
        Tii_Worker_Timer::init($event);
    }

    /**
     * Init data for worker id.
     */
    public static function initIds()
    {
        foreach (self::$workers as $worker_id => $worker) {//init data for worker id
            self::$ids[$worker_id] = array_fill(0, $worker->count, 0);
        }
    }

    /**
     * Set process name.
     *
     * @param string $title
     * @return void
     */
    public static function setProcessTitle($title)
    {
        // >=php 5.5
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title($title);
        } // Need proctitle when php<=5.5 .
        elseif (extension_loaded('proctitle') && function_exists('setproctitle')) {
            @setproctitle($title);
        }
    }

    /**
     * Safe Echo.
     *
     * @param $msg
     */
    public static function stdout($msg)
    {
        if (!function_exists('posix_isatty') || posix_isatty(STDOUT)) {
            echo $msg;
        }
    }

    /**
     * Select the worker processor based on whether to support pcntl extension.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (PHP_SAPI != "cli") {//only for cli.
            exit("This Programe can only be run in CLI mode\r\n");
        }

        static $instance;

        if (!$instance) {
            $instance = Tii::object(Tii::className('Tii_Worker_Processor',
                function_exists('pcntl_signal') ?  'pcntl' : 'WithoutPcntl'//is pcntl?
            ));
        }

        return call_user_func_array([$instance, $name], $arguments);
    }
}

/**
 * Register some protocols
 */
Tii_Event::register('tii.worker.protocols', function ($protocols) {
    //...\n
    $protocols['text'] = [
        'input' => function ($buffer, $connection) {
            if (strlen($buffer) >= $connection->maxPackageSize) {
                $connection->close();
                return 0;
            }
            $pos = strpos($buffer, "\n");
            return $pos === false ? 0 : $pos + 1;
        },
        'encode' => function ($buffer) {
            return $buffer . "\n";
        },
        'decode' => function ($buffer) {
            return trim($buffer);
        },
    ];

    //chunk [data length]data
    $protocols['chunk'] = [
        'input' => function ($buffer) {
            if (strlen($buffer) < 4) return 0;
            $unpack_data = unpack('Ntotal_length', $buffer);
            return $unpack_data['total_length'];
        },
        'encode' => function ($buffer) {
            return pack('N', 4 + strlen($buffer)) . $buffer;
        },
        'decode' => function ($buffer) {
            return substr($buffer, 4);
        }
    ];

    $protocols["chunk.gz"] = [//gzcompress
        'input' => $protocols['chunk']['input'],
        'encode' => function ($buffer) use ($protocols) {
            return call_user_func($protocols['chunk']['encode'], gzcompress($buffer));
        },
        'decode' => function ($buffer) use ($protocols) {
            return gzuncompress(call_user_func($protocols['chunk']['decode'], $buffer));
        }
    ];

    //extra protocols
    foreach (Tii_Event::filter('tii.worker.protocols.extra', ['text', 'chunk', "chunk.gz"]) as $p) {
        $protocols["$p.serialize"] = [//serialize
            'input' => $protocols[$p]['input'],
            'encode' => function ($buffer) use ($protocols, $p) {
                return call_user_func($protocols[$p]['encode'], serialize($buffer));
            },
            'decode' => function ($buffer) use ($protocols, $p) {
                return unserialize(call_user_func($protocols[$p]['decode'], $buffer));
            }
        ];
        $protocols["$p.json"] = [//json
            'input' => $protocols[$p]['input'],
            'encode' => function ($buffer) use ($protocols, $p) {
                return call_user_func($protocols[$p]['encode'], json_encode($buffer));
            },
            'decode' => function ($buffer) use ($protocols, $p) {
                return json_decode(call_user_func($protocols[$p]['decode'], $buffer), true);
            }
        ];
    }

    return $protocols;
});