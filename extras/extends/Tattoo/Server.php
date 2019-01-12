<?php
/**
 * Multi-Process Socket Server
 *
 * <code>
 * $server = new Tattoo_Server(function($request){
 *   print_r($request->getHttpRequest());
 *   $body = json_encode(array(Desire_Time::format(), $_SERVER, $_ENV, $_REQUEST, $_SESSION));
 *   $request->writeHttpResponse(200, $body);
 * });
 * $server->run();
 * </code>
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Server.php 2223 2015-10-09 02:31:09Z alacner $
 */

defined('SIGCLD') || define ('SIGCLD', 17);

class _Tattoo_Server_Http_Request
{
	public $body;
	public $headers = array();
	public $method;
	public $serverProtocol;
	public $path = '';

	public $get = array();
	public $post = array();
	public $request = array();
	public $userdata = null;

	public function __construct($response)
	{
		$this->body = $response->body;
		$this->headers = $response->headers;
		$this->method = $response->method;
		$this->serverProtocol = $response->serverProtocol;
		$this->path = $response->path;

		$response->get && $this->get = $response->get;
		$response->post && $this->post = $response->post;
		$this->request = array_merge($this->get, $this->post);;
	}
}

/**
 * Class _Tattoo_Server_Request_Wrapper is a wrapper for a client socket
 * and define the communication protocol
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Server.php 2223 2015-10-09 02:31:09Z alacner $
 */
class _Tattoo_Server_Request_Wrapper
{
	const HTTP_HEAD_LINE_SEPARATOR = "\r\n";
	const HTTP_EOF = "\r\n\r\n";

	public $name = NULL; //client name, eg: 10.23.33.158:3437
	private $socket = NULL;
	private $length = 0;
	private $isEOF = false;
	public $initialized = false;

	public function __construct($socket, $timeout = 15, $isBlocking = false)
	{
		if (!is_resource($socket)) {
			return;
		}
		$this->socket = $socket;

		stream_set_blocking($socket, $isBlocking);
		stream_set_timeout($socket, $timeout);

		$this->name = stream_socket_get_name($socket, true);
		$this->initialized = true;
	}

	public function read($length = 1024)
	{
		$data = stream_socket_recvfrom($this->socket, $length);
		$len  = strlen($data);
		$this->length += $len;
		if ($length !== $len) $this->isEOF = true;
		return $data;
	}

	public function readAll($length = 1024)
	{
		$data = '';
		while (!$this->isEOF) {
			$data .= $this->read($length);
		}
		return $data;
	}

	public function peek($length = 1)
	{
		return stream_socket_recvfrom($this->socket, $length, STREAM_PEEK);
	}

	public function write($data)
	{
		$data = strval($data);
		$length = strlen($data);
		if ($length == 0) {
			return 0;
		}
		/* in case of send failed */
		$alreay_sent = 0;
		while ($alreay_sent < $length) {
			if (($send = stream_socket_sendto($this->socket, substr($data, $alreay_sent))) < 0) {
				break;
			}
			$alreay_sent += $send;
		}
		return $length;
	}

	public function getHttpRequest()
	{
		$rawRequest = $this->readAll();

		if (!$rawRequest) {
			throw new Exception("Not a correct HTTP request, empty stream data");
		}

		$request = new stdClass();
		list($rawHeaders, $request->body) = explode(self::HTTP_EOF, $rawRequest, 2);

		$request->headers = array();
		$headers = explode(self::HTTP_HEAD_LINE_SEPARATOR, $rawHeaders);

		list($requestMethod, $url, $serverProtocol) = explode(' ', array_shift($headers));

		if (!$requestMethod) {
			throw new Exception("Not a correct HTTP request, stream data: `$rawRequest'");
		}

		$request->method = $requestMethod;
		$request->serverProtocol = $serverProtocol;
		$request->get = array();
		$request->post = array();

		if ($url) {
			$parseUrl = parse_url($url);
			$request->path = isset($parseUrl['path']) ? $parseUrl['path'] : '/';

			if (in_array($request->path, array('/favicon.ico'))) {
				$this->writeHttpResponse(404);
				exit;
			}

			if (isset($parseUrl['query'])) {
				$request->query = $parseUrl['query'];
				parse_str($parseUrl['query'], $request->get);
			}
		}

		foreach($headers as $header) {
			list ($name, $value) = explode(":", $header, 2);
			$name = trim($name);
			$value = trim($value);
			if (isset($request->headers[$name])) {
				if (!is_array($request->headers[$name])) {
					$request->headers[$name] = array($request->headers[$name]);
				}
				$request->headers[$name][] = $value;
			} else {
				$request->headers[$name] = $value;
			}
		}

		$contentType = isset($request->headers['Content-Type']) ? $request->headers['Content-Type'] : '';
		list($contentType, $ctp) = explode(';', $contentType);

		switch(trim($contentType)) {
			case 'application/x-www-form-urlencoded':
				parse_str($request->body, $request->post);
				break;
			case 'multipart/form-data':
				if (preg_match('|boundary=(.*)$|iUs', $ctp, $m)) {
					$formData = explode('--'.trim($m[1]), $request->body);

					foreach($formData as $fd) {
						$fd = trim($fd);
						if (empty($fd) || $fd === '--') continue;
						list($fdh, $fdb) = explode(self::HTTP_EOF, $fd, 2);
						if (preg_match('|name="([^"]+)"|iUs', $fdh, $m1)) {
							$request->post[$m1[1]] = $fdb;
						}
					}
				}
			case 'text/plain'://to default
			default:
		}

		return new _Tattoo_Server_Http_Request($request);
	}

	//Location: http://www.example.com/
	public function writeHttpResponse($header, $body = null, array $headers = array())
	{
		$headerSnapshot = array(
			200 =>  "HTTP/1.1 200 OK\r\nStatus:200 OK",
			403 => "HTTP/1.1 403 Forbidden\r\nStatus:403 Forbidden",
			404 => "HTTP/1.1 404 Not Found\r\nStatus:404 Not Found",
			500 => "HTTP/1.1 500 Internal Server Error\r\nStatus:500 Internal Server Error",
			503 => "HTTP/1.1 503 Service Unavailable\r\nStatus:503 Service Unavailable",
		);

		$header = isset($headerSnapshot[$header]) ? $headerSnapshot[$header] : $header;

		$body = is_null($body) ? '' : $body;

		$headers = array_merge(array(
			"Server" => 'Tattoo_Server/'.Tattoo_Server::VERSION,
			"Via" => $this->name(),
			"Content-Length" => strlen($body),
			"Content-Type" => "text/html",
			'Pragma' => 'no-cache',
			"Cache-Control" => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, no-cache',
			'Access-Control-Allow-Origin' => '*',
			'P3P' => 'CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"',
			"Connection" => "close",
		), $headers);

		$rawHeaders = "$header\r\n";
		foreach($headers as $name => $values) {
			is_array($values) || $values = array($values);
			foreach($values as $value) {
				$rawHeaders .= "$name: $value\r\n";
			}
		}
		$rawHeaders .= "\r\n";
		$this->write($rawHeaders . $body);
	}

	public function name()
	{
		return $this->name;
	}

	public function __destruct()
	{
		if (is_resource($this->socket)) {
			stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
		}
	}
}

class Tattoo_Server
{
	const VERSION = '0.10.3';

	private $daemonMode  = false;
	private $timeout = 15;
	private $isBlocking = false;

	private $isMaster = true;
	private $hostname = null;
	private $port = null;
	private $children = 0;
	private $executor = null;

	private $server = null;
	public $timer; /** @var Desire_Timer */

	/**
	 * @param callable $executor function(_Tattoo_Server_Request_Wrapper, Tattoo_Server, $socket){}
	 * @param mixed $port lt 0, Can be used for port automatically, array(min,max)
	 * @param string $hostname
	 */
	public function __construct(callable $executor, $port = 1206, $hostname = '127.0.0.1')
	{
		if (!extension_loaded("pcntl")) {
			throw new Exception("Desire_Server require pcntl extension loaded");
		}
		/** assure run in cli mode */
		if (substr(php_sapi_name(), 0, 3) !== 'cli') {
			throw new Exception("This Programe can only be run in CLI mode");
		}

		if (!is_callable($executor)) {
			throw new Exception("Illegal argument executor not callable");
		}

		$hostname && $this->hostname = $hostname;

		$this->port = $this->getAvailablePort($port);
		if (!$this->port) {
			throw new Exception("bind() to {$this->hostname }:{$this->port} failed (Address already in use)");
		}
		$this->executor = $executor;
		$this->timer = new Desire_Timer();
	}

	public function getPort()
	{
		return $this->port;
	}

	public function getChildrenNumber()
	{
		return $this->children;
	}

	protected function checkAvailablePort($port, $hostname = '0.0.0.0')
	{
		$fp = @fsockopen($hostname, $port, $errno, $errstr, 1);
		if (!$fp) return $port;
		fclose($fp);
		return -1;
	}

	protected function getAvailablePort($p = -1)
	{
		list($min, $max) = is_array($p) ? $p : ((is_numeric($p) && $p > 0) ? array($p, $p) : array(8000, 9000));

		if (!is_numeric($min) || !is_numeric($max) || $max < $min) {
			throw new Exception("invalid port");
		}

		while(true) {
			for ($port = $min; $port <= $max; $port++) {
				if ($this->checkAvailablePort($port, $this->hostname) > 0) {
					return $port;
				}
			}
			sleep(1);//good luck!!!
		}
	}

	public function setDaemonMode($daemonMode = true)
	{
		$this->daemonMode = $daemonMode;
	}

	public function setTimeout($timeout = 15)
	{
		$this->timeout = $timeout;
	}

	public function setBlocking($isBlocking = true)
	{
		$this->isBlocking = $isBlocking;
	}

	public function run()
	{
		/** no need actually */
		set_time_limit(0);

		$signals = array(
			SIGCHLD => "SIGCHLD",
			SIGINT  => "SIGINT",
			SIGHUP  => "SIGHUP",
			SIGQUIT => "SIGQUIT",
		);

		foreach ($signals as $signal => $name) {
			if (!pcntl_signal($signal, array($this, "handler"))) {
				throw new Exception("Install signal handler for {$name} failed");
			}
		}

		$context = stream_context_create();
		$dns = "tcp://{$this->hostname}:{$this->port}";
		$server = stream_socket_server($dns, $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context);
		if (FALSE === $server) {
			throw new Exception($errstr);
		} else {
			printf("start listening on %s...\n", $dns);
		}

		$this->server  = $server;

		$this->timer->start();
		while ($this->timer->tick()) {

			$socket = @stream_socket_accept($server, $this->timeout);

			if (false !== $socket) {
				//Desire_Logger::debug("accepted connection from `" . stream_socket_get_name($socket, true) . "'", __METHOD__);
				$pid = pcntl_fork();
				if ($pid == 0) { //this is a child
					$this->isMaster = false;
					$this->execute($socket);
					exit(0);
				} else {
					++$this->children;
				}
			}

			pcntl_signal_dispatch();
		}
		return true;
	}

	protected function execute($socket)
	{
		/* set timeout */
		set_time_limit($this->timeout);
		/* ignore all quit signal */
		$signals = array(
			SIGINT  => "SIGINT",
			SIGHUP  => "SIGHUP",
			SIGQUIT => "SIGQUIT",
		);
		foreach ($signals as $signal => $name) {
			pcntl_signal($signal, SIG_IGN);
		}

		$request = new _Tattoo_Server_Request_Wrapper($socket, $this->timeout, $this->isBlocking);
		if ($request->initialized) {
			call_user_func($this->executor, $request, $socket);
		} else {
			Desire_Logger::err("initialized request failed, client \"". stream_socket_get_name($socket, TRUE) . "\"", __METHOD__);
			return false;
		}

		stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
		fclose($socket);
		return true;
	}

	protected function handler($signo)
	{
		switch(intval($signo)) {
			case SIGCLD:
			case SIGCHLD:
				/** declare = 1, that means one signal may be correspond multi-process die */
				while( ($pid = pcntl_wait($status, WNOHANG|WUNTRACED)) > 0 ) {
					if (FALSE === pcntl_wifexited($status)) {
						Desire_Logger::warn("sub proccess {$pid} exited unormally with code {$status}", __METHOD__);
					} else {
						//Desire_Logger::debug("sub proccess {$pid} exited normally", __METHOD__);
					}
					$this->children--;
				}
				break;
			case SIGINT:
			case SIGQUIT:
			case SIGHUP:
				$this->cleanup();
				exit(0);
				break;
			default:
				break;
		}
	}

	protected function cleanup()
	{
		if (!$this->isMaster) {
			return;
		}

		$this->timer->stop();

		while ($this->children > 0) {
			$pid = pcntl_wait($status, WNOHANG | WUNTRACED);
			if ($pid > 0) {
				if (FALSE === pcntl_wifexited($status)) {
					Desire_Logger::warn("sub proccess {$pid} exited unormally with code {$status}", __METHOD__);
				} else {
					Desire_Logger::debug("sub proccess {$pid} exited normally", __METHOD__);
				}
				$this->children--;
			} else {
				continue;
			}
		}

		if ($this->server) {
			stream_socket_shutdown($this->server, STREAM_SHUT_RDWR);
			fclose($this->server);
		}
	}

	public function __toString()
	{
		return "{$this->hostname}:{$this->port}";
	}

	public function __destruct()
	{}
}