<?php
/**
 * This is the PHP client for NSQ - a realtime distributed messaging platform designed to operate at scale,
 * handling billions of messages per day.
 *
 * Class implemented using (p)fsockopen() and class Desire_Http
 * Under NSQ Protocol v0.3.5
 * More information is available at http://nsq.io/
 * Refer to https://github.com/davegardnerisme/nsqphp
 *
 * @author  Alacner Zhang <alacner@gmail.com>
 * @version $Id: Nsq.php 2090 2015-09-07 02:33:46Z alacner $
 */

class NsqException extends Desire_Exception
{}

final class NsqSocket extends Tattoo_Socket
{}

final class NsqLoop
{
	const QUANTUM_INTERVAL = 1000000;

	private $timers;
	private $running = false;
	private $readStreams = array();
	private $readListeners = array();
	private $writeStreams = array();
	private $writeListeners = array();

	public function __construct()
	{
		$this->timers = new Desire_Timer;
	}

	public function addReadStream($stream, $listener)
	{
		$id = (int) $stream;

		if (!isset($this->readStreams[$id])) {
			$this->readStreams[$id] = $stream;
			$this->readListeners[$id] = $listener;
		}
	}

	public function addWriteStream($stream, $listener)
	{
		$id = (int) $stream;

		if (!isset($this->writeStreams[$id])) {
			$this->writeStreams[$id] = $stream;
			$this->writeListeners[$id] = $listener;
		}
	}

	public function removeReadStream($stream)
	{
		$id = (int) $stream;

		unset(
		$this->readStreams[$id],
		$this->readListeners[$id]
		);
	}

	public function removeWriteStream($stream)
	{
		$id = (int) $stream;

		unset(
		$this->writeStreams[$id],
		$this->writeListeners[$id]
		);
	}

	public function removeStream($stream)
	{
		$this->removeReadStream($stream);
		$this->removeWriteStream($stream);
	}

	public function addTimer($interval, $callback)
	{
		return $this->timers->add($callback, $interval);
	}

	public function addPeriodicTimer($interval, $callback)
	{
		return $this->timers->add($callback, $interval, true);
	}

	public function cancelTimer($signature)
	{
		$this->timers->cancel($signature);
	}

	protected function getNextEventTimeInMicroSeconds()
	{
		$nextEvent = $this->timers->getFirst();

		if (null === $nextEvent) {
			return self::QUANTUM_INTERVAL;
		}

		$currentTime = microtime(true);
		if ($nextEvent > $currentTime) {
			return ($nextEvent - $currentTime) * 1000000;
		}

		return 0;
	}

	protected function sleepOnPendingTimers()
	{
		if ($this->timers->isEmpty()) {
			$this->running = false;
		} else {
			// We use usleep() instead of stream_select() to emulate timeouts
			// since the latter fails when there are no streams registered for
			// read / write events. Blame PHP for us needing this hack.
			usleep($this->getNextEventTimeInMicroSeconds());
		}
	}

	protected function runStreamSelect()
	{
		$read = $this->readStreams ?: null;
		$write = $this->writeStreams ?: null;
		$except = null;

		if (!$read && !$write) {
			$this->sleepOnPendingTimers();

			return;
		}

		if (stream_select($read, $write, $except, 0, $this->getNextEventTimeInMicroSeconds()) > 0) {
			if ($read) {
				foreach ($read as $stream) {
					$listener = $this->readListeners[(int) $stream];
					if (call_user_func($listener, $stream, $this) === false) {
						$this->removeReadStream($stream);
					}
				}
			}

			if ($write) {
				foreach ($write as $stream) {
					if (!isset($this->writeListeners[(int) $stream])) {
						continue;
					}

					$listener = $this->writeListeners[(int) $stream];
					if (call_user_func($listener, $stream, $this) === false) {
						$this->removeWriteStream($stream);
					}
				}
			}
		}
	}

	public function tick()
	{
		$this->timers->tick();
		$this->runStreamSelect();

		return $this->running;
	}

	public function run()
	{
		// @codeCoverageIgnoreStart
		$this->running = true;

		while ($this->tick()) {
			// NOOP
		}
		// @codeCoverageIgnoreEnd
	}

	public function stop()
	{
		// @codeCoverageIgnoreStart
		$this->running = false;
		// @codeCoverageIgnoreEnd
	}
}

/**
 * Represents a pool of sockets to one or more NSQD servers
 */
final class NsqSocketPool implements Iterator, Countable
{
	/**
	 * Sockets
	 *
	 * @var array [] = NsqSocket $socket
	 */
	private $sockets = array();

	/**
	 * Add socket
	 *
	 * @param NsqSocket $socket
	 */
	public function add(NsqSocket $socket)
	{
		$this->sockets[] = $socket;
	}

	/**
	 * Test if has socket
	 *
	 * Remember that the sockets are lazy-initialised so we can create
	 * socket instances to test with without incurring a socket connection.
	 *
	 * @param NsqSocket $socket
	 *
	 * @return boolean
	 */
	public function hasConnection(NsqSocket $socket)
	{
		return $this->find($socket->getSocket()) ? TRUE : FALSE;
	}

	/**
	 * Find socket from socket/host
	 *
	 * @param Resource|string $socketOrHost
	 *
	 * @return NsqSocket|NULL Will return NULL if not found
	 */
	public function find($socketOrHost)
	{
		foreach ($this->sockets as $conn) {
			if (is_string($socketOrHost) && (string)$conn === $socketOrHost) {
				return $conn;
			} elseif ($conn->getSocket() === $socketOrHost) {
				return $conn;
			}
		}
		return NULL;
	}

	/**
	 * Get key of current item as string
	 *
	 * @return string
	 */
	public function key()
	{
		return key($this->sockets);
	}

	/**
	 * Test if current item valid
	 *
	 * @return boolean
	 */
	public function valid()
	{
		return (current($this->sockets) === FALSE) ? FALSE : TRUE;
	}

	/**
	 * Fetch current value
	 *
	 * @return mixed
	 */
	public function current()
	{
		return current($this->sockets);
	}

	/**
	 * Go to next item
	 */
	public function next()
	{
		next($this->sockets);
	}

	/**
	 * Rewind to start
	 */
	public function rewind()
	{
		reset($this->sockets);
	}

	/**
	 * Move to end
	 */
	public function end()
	{
		end($this->sockets);
	}

	/**
	 * Get count of items
	 *
	 * @return integer
	 */
	public function count()
	{
		return count($this->sockets);
	}

	/**
	 * Shuffle connections
	 */
	public function shuffle()
	{
		shuffle($this->sockets);
	}
}

final class NsqReader
{
	/**
	 * Frame types
	 */
	const FRAME_TYPE_RESPONSE = 0;
	const FRAME_TYPE_ERROR = 1;
	const FRAME_TYPE_MESSAGE = 2;

	/**
	 * Heartbeat response content
	 */
	const HEARTBEAT = '_heartbeat_';

	/**
	 * OK response content
	 */
	const OK = 'OK';

	/**
	 * Read frame
	 *
	 * @throws ReadException If we have a problem reading the core frame header
	 *      (data size + frame type)
	 * @throws ReadException If we have a problem reading the frame data
	 *
	 * @return array With keys: type, data
	 */
	public function readFrame(NsqSocket $socket)
	{
		$size = $frameType = NULL;
		try {
			$size = $this->readInt($socket);
			$frameType = $this->readInt($socket);
		} catch (Exception $e) {
			throw new NsqException("Error reading message frame [$size, $frameType] (" . $e->getMessage() . ")", NULL, $e);
		}

		$frame = array(
			'type'  => $frameType,
			'size'  => $size
		);

		try {
			switch ($frameType) {
				case self::FRAME_TYPE_RESPONSE:
					$frame['response'] = $this->readString($socket, $size-4);
					break;
				case self::FRAME_TYPE_ERROR:
					$frame['error'] = $this->readString($socket, $size-4);
					break;
				case self::FRAME_TYPE_MESSAGE:
					$frame['ts'] = $this->readLong($socket);
					$frame['attempts'] = $this->readShort($socket);
					$frame['id'] = $this->readString($socket, 16);
					$frame['payload'] = $this->readString($socket, $size - 30);
					break;
				default:
					throw new Exception($this->readString($socket, $size-4));
					break;
			}
		} catch (Exception $e) {
			throw new NsqException("Error reading frame details [$size, $frameType]", NULL, $e);
		}

		return $frame;
	}

	/**
	 * Test if frame is a response frame (optionally with content $response)
	 *
	 * @param array $frame
	 * @param string|NULL $response If provided we'll check for this specific
	 *      response
	 *
	 * @return boolean
	 */
	public function frameIsResponse(array $frame, $response = NULL)
	{
		return isset($frame['type'], $frame['response'])
		&& $frame['type'] === self::FRAME_TYPE_RESPONSE
		&& ($response === NULL || $frame['response'] === $response);
	}

	/**
	 * Test if frame is a message frame
	 *
	 * @param array $frame
	 *
	 * @return boolean
	 */
	public function frameIsMessage(array $frame)
	{
		return isset($frame['type'], $frame['payload'])
		&& $frame['type'] === self::FRAME_TYPE_MESSAGE;
	}

	/**
	 * Test if frame is heartbeat
	 *
	 * @param array $frame
	 *
	 * @return boolean
	 */
	public function frameIsHeartbeat(array $frame)
	{
		return $this->frameIsResponse($frame, self::HEARTBEAT);
	}

	/**
	 * Test if frame is OK
	 *
	 * @param array $frame
	 *
	 * @return boolean
	 */
	public function frameIsOk(array $frame)
	{
		return $this->frameIsResponse($frame, self::OK);
	}

	/**
	 * Read and unpack short integer (2 bytes) from connection
	 *
	 * @param NsqSocket $socket
	 *
	 * @return integer
	 */
	private function readShort(NsqSocket $socket)
	{
		list(,$res) = unpack('n', $socket->read(2));
		return $res;
	}

	/**
	 * Read and unpack integer (4 bytes) from connection
	 *
	 * @param NsqSocket $socket
	 *
	 * @return integer
	 */
	private function readInt(NsqSocket $socket)
	{
		list(,$res) = unpack('N', $socket->read(4));
		if ((PHP_INT_SIZE !== 4)) {
			$res = sprintf("%u", $res);
		}
		return (int)$res;
	}

	/**
	 * Read and unpack long (8 bytes) from connection
	 *
	 * @param NsqSocket $socket
	 *
	 * @return string We return as string so it works on 32 bit arch
	 */
	private function readLong(NsqSocket $socket)
	{
		$hi = unpack('N', $socket->read(4));
		$lo = unpack('N', $socket->read(4));

		// workaround signed/unsigned braindamage in php
		$hi = sprintf("%u", $hi[1]);
		$lo = sprintf("%u", $lo[1]);

		return bcadd(bcmul($hi, "4294967296" ), $lo);
	}

	/**
	 * Read and unpack string; reading $size bytes
	 *
	 * @param NsqSocket $socket
	 * @param integer $size
	 *
	 * @return string
	 */
	private function readString(NsqSocket $socket, $size)
	{
		$temp = unpack("c{$size}chars", $socket->read($size));
		$out = "";
		foreach($temp as $v) {
			if ($v > 0) {
				$out .= chr($v);
			}
		}
		return $out;
	}
}

/**
 * Class NsqWriter
 */
final class NsqWriter
{
	/**
	 * "Magic" identifier - for version we support
	 */
	const MAGIC_V2 = "  V2";

	/**
	 * Magic hello
	 *
	 * @return string
	 */
	public function magic()
	{
		return self::MAGIC_V2;
	}

	/**
	 * Subscribe [SUB]
	 *
	 * @param string $topic
	 * @param string $channel
	 * @param string $shortId
	 * @param string $longId
	 *
	 * @return string
	 */
	public function subscribe($topic, $channel, $shortId, $longId)
	{
		return $this->command('SUB', $topic, $channel, $shortId, $longId);
	}

	/**
	 * Publish [PUB]
	 * the fast pack way, but may be unsafe
	 *
	 * @param string $topic
	 * @param string $message
	 *
	 * @return string
	 */
	public function publish($topic, $message)
	{
		$cmd = $this->command('PUB', $topic);
		$size = pack('N', strlen($message));
		return $cmd . $size . $message;
	}

	/**
	 * Publish [PUB]
	 * the safe way, but is time cost
	 *
	 * @param string $topic
	 * @param string $message
	 *
	 * @return string
	 */
	public function publish_safe($topic, $message)
	{
		$cmd = $this->command('PUB', $topic);
		$data = $this->packString($message);
		$size = pack('N', strlen($data));
		return $cmd . $size . $data;
	}

	/**
	 * Ready [RDY]
	 *
	 * @param integer $count
	 *
	 * @return string
	 */
	public function ready($count)
	{
		return $this->command('RDY', $count);
	}

	/**
	 * Finish [FIN]
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function finish($id)
	{
		return $this->command('FIN', $id);
	}

	/**
	 * Requeue [REQ]
	 *
	 * @param string $id
	 * @param integer $timeMs
	 *
	 * @return string
	 */
	public function requeue($id, $timeMs)
	{
		return $this->command('REQ', $id, $timeMs);
	}

	/**
	 * Touch [TOUCH]
	 * Reset the timeout for an in-flight message
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function touch($id)
	{
		return $this->command('TOUCH', $id);
	}

	/**
	 * Auth [AUTH]
	 *
	 * @return string
	 */
	public function auth()
	{
		return $this->command('AUTH');
	}

	/**
	 * No-op [NOP]
	 *
	 * @return string
	 */
	public function nop()
	{
		return $this->command('NOP');
	}

	/**
	 * Cleanly close [CLS]
	 *
	 * @return string
	 */
	public function close()
	{
		return $this->command('CLS');
	}

	/**
	 * Command
	 *
	 * @return string
	 */
	private function command()
	{
		$args = func_get_args();
		$cmd = array_shift($args);
		return sprintf("%s %s%s", $cmd, implode(' ', $args), "\n");
	}

	/**
	 * Pack string -> binary
	 *
	 * @param string $str
	 *
	 * @return string Binary packed
	 */
	private function packString($str)
	{
		$outStr = "";
		$len = strlen($str);
		for ($i = 0; $i < $len; $i++) {
			$outStr .= pack("c", ord(substr($str, $i, 1)));
		}
		return $outStr;
	}
}


final class NsqMessage
{
	/**
	 * Construct from frame
	 *
	 * @param array $frame
	 */
	public static function fromFrame(array $frame)
	{
		return new NsqMessage(
			$frame['payload'],
			$frame['id'],
			$frame['attempts'],
			$frame['ts']
		);
	}

	/**
	 * Message payload - string
	 *
	 * @var string
	 */
	private $data = '';

	/**
	 * Message ID; if relevant
	 *
	 * @var string|NULL
	 */
	private $id = NULL;

	/**
	 * How many attempts have been made; if relevant
	 *
	 * @var integer|NULL
	 */
	private $attempts = NULL;

	/**
	 * Timestamp - UNIX timestamp in seconds (incl. fractions); if relevant
	 *
	 * @var float|NULL
	 */
	private $ts = NULL;

	/**
	 * @var Exception
	 */
	private $ex = NULL;

	/**
	 * Constructor
	 *
	 * @param string $data
	 * @param string|NULL $id The message ID in hex (as ASCII)
	 * @param integer|NULL $attempts How many attempts have been made on msg so far
	 * @param float|NULL $ts Timestamp (nanosecond precision, as number of seconds)
	 */
	public function __construct($data, $id = NULL, $attempts = NULL, $ts = NULL)
	{
		$this->data = $data;
		$this->id = $id;
		$this->attempts = $attempts;
		$this->ts = $ts;
	}

	/**
	 * Get message payload
	 *
	 * @return string
	 */
	public function getPayload()
	{
		return $this->data;
	}

	/**
	 * Get message ID
	 *
	 * @return string|NULL
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get attempts
	 *
	 * @return integer|NULL
	 */
	public function getAttempts()
	{
		return $this->attempts;
	}

	/**
	 * Get timestamp
	 *
	 * @return float|NULL
	 */
	public function getTimestamp()
	{
		return $this->ts;
	}

	/**
	 * Set Exception
	 */
	public function setException(Exception $ex)
	{
		$this->ex = $ex;
	}

	/**
	 * Get Exception
	 *
	 * @return Exception
	 */
	public function getException()
	{
		return $this->ex;
	}

	/**
	 * If callback succeed?
	 * @return bool
	 */
	public function isSuccess()
	{
		return is_null($this->ex);
	}
}

/**
 * Class NsqResult
 */
final class NsqResult
{
	public $status_code = 0;
	public $status_txt = 'NONE';
	public $data = NULL;
	public $remote = NULL;

	public function __construct($data = NULL, $error = false)
	{
		if ($error) {
			$this->status_txt = $data;
		} else {
			try {
				$arr = json_decode($data, true);
				if (!is_array($arr)) {
					throw new Exception('json decode error');
				}

				foreach($arr as $k => $v) {
					$this->$k = $v;
				}

			} catch (Exception $e) {//json decode exception, ignore
				if ($data === 'OK') {
					$this->status_code = 200;
				}
				$this->status_txt = $data;
			}
		}
	}

	/**
	 * @param $statusCode
	 * @return $this
	 */
	public function setStatusCode($statusCode)
	{
		$this->status_code = $statusCode;
		return $this;
	}

	/**
	 * @param $statusTxt
	 * @return $this
	 */
	public function setStatusTxt($statusTxt)
	{
		$this->status_txt = $statusTxt;
		return $this;
	}

	/**
	 * @param $data
	 * @return $this
	 */
	public function setData($data)
	{
		$this->data = $data;
		return $this;
	}

	/**
	 * @param $remote
	 * @return $this
	 */
	public function setRemote($remote)
	{
		$this->remote = $remote;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSucceed()
	{
		return $this->status_txt === 'OK';
	}
}


final class Tattoo_Nsq
{
	/**
	 * Connection pool for subscriptions
	 *
	 * @var NsqSocketPool
	 */
	private $subSocketPool = array();

	/**
	 * Connection pool for publishing
	 *
	 * @var NsqSocketPool
	 */
	private $pubSocketPool = array();

	private $config = array(
		'tcp-address' => ['127.0.0.1:4160'],
		'http-address' => ['127.0.0.1:4161'],
		'timeout' => array(
			'connection' => 3,
			'read_write' => 3,
			'read_wait' => 15,
		),
	);

	/**
	 * Long ID (of who we are)
	 *
	 * @var string
	 */
	private $longId;

	/**
	 * Short ID (of who we are)
	 *
	 * @var string
	 */
	private $shortId;

	/**
	 * Event loop
	 *
	 * @var NsqLoop
	 */
	private $loop;

	/**
	 * @param array $config
	 */
	public function __construct(array $config = array())
	{
		$config || $config = Desire_Config::get("tattoo.nsq", array());
		$this->config = array_merge($this->config, $config);

		$hn = gethostname();
		$parts = explode('.', $hn);
		$this->shortId = $parts[0];
		$this->longId = $hn;

		$this->subSocketPool = new NsqSocketPool;

		$this->loop = new NsqLoop;

		$this->reader = new NsqReader;
		$this->writer = new NsqWriter;
	}

	/**
	 * @param $url
	 * @param string $data
	 * @return NsqResult
	 */
	private function _httpRequest($url, $data = '')
	{
		try {
			$response = Desire_Http::post($url, $data);
			return new NsqResult($response->data);
		} catch(Exception $e) {
			return new NsqResult($e->getMessage(), true);
		}
	}

	/**
	 * @param $uri
	 * @param string $data
	 * @return array [NsqResult]
	 */
	public function nsqlookupds($uri, $data = '')
	{
		$output = array();
		foreach($this->config['http-address'] as $addr) {
			$url = sprintf('http://%s%s', $addr, $uri);
			$output[$addr] = $this->_httpRequest($url, $data);
			$output[$addr]->setRemote($addr);
		}
		return $output;
	}

	/**
	 * @param $uri
	 * @param string $data
	 * @return NsqResult
	 */
	public function nsqlookupd($uri, $data = '')
	{
		shuffle($this->config['http-address']);
		foreach($this->config['http-address'] as $addr) {
			$url = sprintf('http://%s%s', $addr, $uri);
			$result = $this->_httpRequest($url, $data);
			$result->setRemote($addr);
			if ($result->isSucceed()) {
				return $result;
			}
		}
		return new NsqResult();
	}

	/**
	 * Returns a list of producers for a topic
	 * @param string $topic the topic to list producers for
	 * @return NsqResult
	 */
	public function lookup($topic)
	{
		return $this->nsqlookupd('/lookup?topic='.$topic);
	}



	/**
	 * Returns a list of all known topics
	 * @return NsqResult
	 */
	public function topics()
	{
		return $this->nsqlookupd('/topics');
	}

	/**
	 * Returns a list of all known channels of a topic
	 * @param string $topic the topic to list channels for
	 * @return NsqResult
	 */
	public function channels($topic)
	{
		return $this->nsqlookupd('/channels?topic='.urlencode($topic));
	}

	/**
	 * Returns a list of all known nsqd
	 * @return NsqResult
	 */
	public function nodes()
	{
		return $this->nsqlookupd('/nodes');
	}

	/**
	 * Deletes an existing topic
	 * @param $topic
	 * @return array [NsqResult]
	 */
	public function deleteTopic($topic)
	{
		return $this->nsqlookupds('/delete_topic?topic='.urlencode($topic));
	}

	/**
	 * Deletes an existing channel of an existing topic
	 * @param $topic the existing topic
	 * @param $channel the existing channel to delete
	 * @return array [NsqResult]
	 */
	public function deleteChannel($topic, $channel)
	{
		return $this->nsqlookupds('/delete_channel?topic='.urlencode($topic).'&channel='.$channel);
	}

	/**
	 * Tombstones a specific producer of an existing topic. See deletion and tombstones.
	 * @see http://nsq.io/components/nsqlookupd.html#deletion_tombstones
	 * @param $topic the existing topic
	 * @param $node the producer (nsqd) to tombstone (identified by <broadcast_address>:<http_port>)
	 * @return array [NsqResult]
	 */
	public function tombstoneTopicProducer($topic, $node)
	{
		return $this->nsqlookupds('/tombstone_topic_producer?topic='.urlencode($topic).'&node='.$node);
	}

	/**
	 * @return array
	 */
	private function getNsqdHttpAddrs()
	{
		$httpAddrs = array();

		$nodes = $this->nodes();
		if (!$nodes->isSucceed()) return $httpAddrs;

		foreach ($nodes->data['producers'] as $prod) {
			if (isset($prod['address'])) {
				$address = $prod['address'];
			} else {
				$address = $prod['broadcast_address'];
			}
			$h = "{$address}:{$prod['http_port']}";
			if (!in_array($h, $httpAddrs)) {
				$httpAddrs[] = $h;
			}
		}

		shuffle($httpAddrs);

		return $httpAddrs;
	}

	/**
	 * @param $uri
	 * @param string $data
	 * @return array [NsqResult]
	 */
	public function nsqds($uri, $data = '')
	{
		$output = array();
		foreach($this->getNsqdHttpAddrs() as $addr) {
			$url = sprintf('http://%s%s', $addr, $uri);
			$output[$addr] = $this->_httpRequest($url, $data);
			$output[$addr]->setRemote($addr);
		}
		return $output;
	}

	/**
	 * @param $uri
	 * @param string $data
	 * @return NsqResult
	 */
	public function nsqd($uri, $data = '')
	{
		foreach($this->getNsqdHttpAddrs() as $addr) {
			$url = sprintf('http://%s%s', $addr, $uri);
			$result = $this->_httpRequest($url, $data);
			$result->setRemote($addr);
			if ($result->isSucceed()) {
				return $result;
			}
		}
		return new NsqResult();
	}

	/**
	 * @param $topic the topic to publish to
	 * @param $data the raw message bytes
	 * @return NsqResult
	 */
	public function pub($topic, $data = '')
	{
		return $this->nsqd('/pub?topic='.urlencode($topic), (string)$data);
	}

	/**
	 * @param $topic the topic to publish to
	 * @param $data body - `\n` separated raw message bytes
	 * @param bool $binary bool ('true' or 'false') to enable binary mode
	 * @return NsqResult
	 */
	public function mpub($topic, $data = '', $binary = false)
	{
		return $this->nsqd('/mpub?topic='.urlencode($topic).($binary ? '&binary=true' : ''), (string)$data);
	}

	/**
	 * Create a topic
	 * @param $topic the topic to create
	 * @return array [NsqResult]
	 */
	public function topicCreate($topic)
	{
		return $this->nsqds('/topic/create?topic='.urlencode($topic));
	}

	/**
	 * Delete an existing topic (and all channels)
	 * @param $topic the existing topic to delete
	 * @return array [NsqResult]
	 */
	public function topicDelete($topic)
	{
		return $this->nsqds('/topic/delete?topic='.urlencode($topic));
	}

	/**
	 * Empty all the queued messages (in-memory and disk) for an existing topic
	 * @param $topic the existing topic to empty
	 * @return array [NsqResult]
	 */
	public function topicEmpty($topic)
	{
		return $this->nsqds('/topic/empty?topic='.urlencode($topic));
	}

	/**
	 * Pause message flow to all channels on an existing topic (messages will queue at topic)
	 * @param $topic the existing topic
	 * @return array [NsqResult]
	 */
	public function topicPause($topic)
	{
		return $this->nsqds('/topic/pause?topic='.urlencode($topic));
	}

	/**
	 * Resume message flow to channels of an existing, paused, topic
	 * @param $topic the existing topic
	 * @return array [NsqResult]
	 */
	public function topicUnpause($topic)
	{
		return $this->nsqds('/topic/unpause?topic='.urlencode($topic));
	}

	/**
	 * Create a channel for an existing topic
	 * @param $topic the existing topic
	 * @param $channel the channel to create
	 * @return array [NsqResult]
	 */
	public function channelCreate($topic, $channel)
	{
		return $this->nsqds('/channel/create?topic='.urlencode($topic).'&channel='.$channel);
	}

	/**
	 * Delete an existing channel for an existing topic
	 * @param $topic the existing topic
	 * @param $channel the existing channel to delete
	 * @return array [NsqResult]
	 */
	public function channelDelete($topic, $channel)
	{
		return $this->nsqds('/channel/delete?topic='.urlencode($topic).'&channel='.$channel);
	}

	/**
	 * Empty all the queued messages (in-memory and disk) for an existing channel
	 * @param $topic the existing topic
	 * @param $channel the existing channel to empty
	 * @return array [NsqResult]
	 */
	public function channelEmpty($topic, $channel)
	{
		return $this->nsqds('/channel/empty?topic='.urlencode($topic).'&channel='.$channel);
	}

	/**
	 * Pause message flow to consumers of an existing channel (messages will queue)
	 * @param $topic the existing topic
	 * @param $channel the existing channel to pause
	 * @return array [NsqResult]
	 */
	public function channelPause($topic, $channel)
	{
		return $this->nsqds('/channel/pause?topic='.urlencode($topic).'&channel='.$channel);
	}

	/**
	 * Resume message flow to consumers of an existing, paused, channel
	 * @param $topic the existing topic
	 * @param $channel the existing channel to unpause
	 * @return array [NsqResult]
	 */
	public function channelUnpause($topic, $channel)
	{
		return $this->nsqds('/channel/unpause?topic='.urlencode($topic).'&channel='.$channel);
	}

	/**
	 * @return array [NsqResult]
	 */
	public function ping()
	{
		return array_merge($this->nsqlookupds('/ping'), $this->nsqds('/ping'));
	}

	/**
	 * @return array [NsqResult]
	 */
	public function info()
	{
		return array_merge($this->nsqlookupds('/info'), $this->nsqds('/info'));
	}

	/**
	 * Return internal instrumented statistics
	 * @param $topic
	 * @return array [NsqResult]
	 */
	public function stats($topic = NULL, $channel = NULL)
	{
		$resArr = $this->nsqds('/stats?format=json');
		if (is_null($topic)) return $resArr;

		$resTopic = array();
		foreach($resArr as $r => $res) {
			if (!$res->isSucceed()) {
				continue;
			}
			foreach($res->data['topics'] as $t) {
				if ($topic === $t['topic_name']) {
					$result = new NsqResult();
					$result->setStatusCode($res->status_code);
					$result->setStatusTxt($res->status_txt);
					$result->setData($t);
					$result->setRemote($res->remote);
					$resTopic[$r] = $result;
					break;
				}
			}
		}

		if (is_null($channel)) return $resTopic;

		$resChannel = array();
		foreach($resTopic as $r => $res) {
			if (!$res->isSucceed()) {
				continue;
			}
			foreach($res->data['channels'] as $c) {
				if ($channel === $c['channel_name']) {
					$result = new NsqResult();
					$result->setStatusCode($res->status_code);
					$result->setStatusTxt($res->status_txt);
					$result->setData($c);
					$result->setRemote($res->remote);
					$resChannel[$r] = $result;
					break;
				}
			}
		}

		return $resChannel;
	}

	/**
	 * @param $topic
	 * @return array
	 */
	private function getNsqdTcpAddrs($topic)
	{
		$tcpAddrs = array();
		$lookup = $this->lookup($topic);
		if (!$lookup->isSucceed()) return $tcpAddrs;

		foreach ($lookup->data['producers'] as $prod) {
			if (isset($prod['address'])) {
				$address = $prod['address'];
			} else {
				$address = $prod['broadcast_address'];
			}
			$h = "{$address}:{$prod['tcp_port']}";
			if (!in_array($h, $tcpAddrs)) {
				$tcpAddrs[] = $h;
			}
		}

		shuffle($tcpAddrs);

		return $tcpAddrs;
	}

	/**
	 * @param $topic
	 * @param $channel
	 * @param $callback
	 * @param $requeueStrategy
	 * @return $this
	 * @throws InvalidArgumentException
	 */
	public function sub($topic, $channel, $callback, $requeueStrategy = NUll)
	{
		if (!is_callable($callback)) {
			throw new InvalidArgumentException(
				'"callback" invalid; expecting a PHP callable'
			);
		}

		// we need to instantiate a new connection for every nsqd that we need
		// to fetch messages from for this topic/channel
		$tcpAddrs = $this->getNsqdTcpAddrs($topic);

		//sub now ...
		Desire_Logger::debug("Found the following hosts for topic \"$topic\": " . implode(',', $tcpAddrs));

		foreach ($tcpAddrs as $addr) {
			$parts = explode(':', $addr);
			$conn = new NsqSocket(
				$parts[0],
				isset($parts[1]) ? $parts[1] : NULL,
				$this->config['timeout']['connection'],
				$this->config['timeout']['read_write'],
				$this->config['timeout']['read_wait'],
				TRUE    // non-blocking
			);

			Desire_Logger::info("Connecting to {$addr} and saying hello");

			$conn->write($this->writer->magic());
			$this->subSocketPool->add($conn);
			$socket = $conn->getSocket();
			$nsq = $this;
			$this->loop->addReadStream($socket, function ($socket) use ($nsq, $callback, $requeueStrategy, $topic, $channel) {
				try {
					$nsq->readAndDispatchMessage($socket, $topic, $channel, $callback, $requeueStrategy);
				} catch (Exception $e) {
					Desire_Logger::err("readAndDispatchMessage error:" . $e->getMessage());
				}
			});

			// subscribe
			$conn->write($this->writer->subscribe($topic, $channel, $this->shortId, $this->longId));
			$conn->write($this->writer->ready(1));
		}

		return $this;
	}

	/**
	 * Run subscribe event loop
	 *
	 * @param int $timeout (default=0) timeout in seconds
	 */
	public function run($timeout = 0)
	{
		if ($timeout > 0) {
			$that = $this;
			$this->loop->addTimer($timeout, function () use ($that) {
				$that->stop();
			});
		}
		$this->loop->run();
	}

	/**
	 * Stop subscribe event loop
	 */
	public function stop()
	{
		$this->loop->stop();
	}


	/**
	 * Read/dispatch callback for async sub loop
	 *
	 * @param Resource $socket The socket that a message is available on
	 * @param string $topic The topic subscribed to that yielded this message
	 * @param string $channel The channel subscribed to that yielded this message
	 * @param callable $callback The callback to execute to process this message  function($msg){}
	 * @param callable $requeueStrategy function($isSuccessed, $msg, $e){}
	 */
	public function readAndDispatchMessage($socket, $topic, $channel, $callback, $requeueStrategy = NULL)
	{
		$connection = $this->subSocketPool->find($socket);
		$frame = $this->reader->readFrame($connection);

		Desire_Logger::debug(sprintf(
			'Read frame for topic=%s channel=%s [%s] %s',
			$topic, $channel, (string)$connection, json_encode($frame)
		));

		// intercept errors/responses
		if ($this->reader->frameIsHeartbeat($frame)) {
			Desire_Logger::debug(sprintf('HEARTBEAT [%s]', (string)$connection));
			$connection->write($this->writer->nop());
		} elseif ($this->reader->frameIsMessage($frame)) {
			$msg = NsqMessage::fromFrame($frame);

			try {
				call_user_func($callback, $msg);

				if (is_callable($requeueStrategy) && ($delay = call_user_func($requeueStrategy, $msg))) {
					$connection->write($this->writer->requeue($msg->getId(), $delay));
				} else {
					$connection->write($this->writer->finish($msg->getId()));
				}
				$connection->write($this->writer->ready(1));

			} catch (Exception $e) {
				$msg->setException($e);

				Desire_Logger::warn(sprintf('Error processing [%s] "%s": %s', (string)$connection, $msg->getId(), $e->getMessage()));
				// requeue message according to backoff strategy; continue
				if (is_callable($requeueStrategy) && ($delay = call_user_func($requeueStrategy, $msg))) {
					Desire_Logger::debug(sprintf('Requeuing [%s] "%s" with delay "%s"', (string)$connection, $msg->getId(), $delay));
					$connection->write($this->writer->requeue($msg->getId(), $delay));
					$connection->write($this->writer->ready(1));
				} else {
					Desire_Logger::debug(sprintf('Not requeuing [%s] "%s"', (string)$connection, $msg->getId()));
				}
			}
		} elseif ($this->reader->frameIsOk($frame)) {
			Desire_Logger::debug(sprintf('Ignoring "OK" frame in SUB loop'));
		} else {
			// @todo handle error responses a bit more cleverly
			throw new Exception("Error/unexpected frame received: " . json_encode($frame));
		}
	}

	/**
	 * Connection callback
	 *
	 * @param NsqSocket $socket
	 */
	public function connectionCallback(NsqSocket $socket)
	{
		Desire_Logger::info("Connecting to " . (string)$socket . " and saying hello");
		$socket->write($this->writer->magic());
	}

	public function __destruct()
	{
		// say goodbye to each connection
		foreach ($this->subSocketPool as $connection) {
			$connection->write($this->writer->close());
			Desire_Logger::info(sprintf('nsq closing [%s]', (string)$connection));
		}
	}
}