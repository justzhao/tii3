<?php
/**
 * Tattoo_Cache_MemcacheSASL extends Desire_Cache_Abstract
 */
/**
 * Class MemcacheSASL
 */
class MemcacheSASL
{
	protected $_request_format = 'CCnCCnNNNN';
	protected $_response_format = 'Cmagic/Copcode/nkeylength/Cextralength/Cdatatype/nstatus/Nbodylength/NOpaque/NCAS1/NCAS2';

	const OPT_COMPRESSION = -1001;

	const MEMC_VAL_TYPE_MASK = 0xf;
	const MEMC_VAL_IS_STRING = 0;
	const MEMC_VAL_IS_LONG = 1;
	const MEMC_VAL_IS_DOUBLE = 2;
	const MEMC_VAL_IS_BOOL = 3;
	const MEMC_VAL_IS_SERIALIZED = 4;

	const MEMC_VAL_COMPRESSED = 16; // 2^4

	protected function _build_request($data)
	{
		$valuelength = $extralength = $keylength = 0;
		if (array_key_exists('extra', $data)) {
			$extralength = strlen($data['extra']);
		}
		if (array_key_exists('key', $data)) {
			$keylength = strlen($data['key']);
		}
		if (array_key_exists('value', $data)) {
			$valuelength = strlen($data['value']);
		}
		$bodylength = $extralength + $keylength + $valuelength;
		$ret = pack($this->_request_format,
			0x80,
			$data['opcode'],
			$keylength,
			$extralength,
			array_key_exists('datatype', $data) ? $data['datatype'] : null,
			array_key_exists('status', $data) ? $data['status'] : null,
			$bodylength,
			array_key_exists('Opaque', $data) ? $data['Opaque'] : null,
			array_key_exists('CAS1', $data) ? $data['CAS1'] : null,
			array_key_exists('CAS2', $data) ? $data['CAS2'] : null
		);

		if (array_key_exists('extra', $data)) {
			$ret .= $data['extra'];
		}

		if (array_key_exists('key', $data)) {
			$ret .= $data['key'];
		}

		if (array_key_exists('value', $data)) {
			$ret .= $data['value'];
		}
		return $ret;
	}

	protected function _show_request($data)
	{
		$array = unpack($this->_response_format, $data);
		return $array;
	}

	protected function _send($data)
	{
		$send_data = $this->_build_request($data);
		fwrite($this->_fp, $send_data);
		return $send_data;
	}

	protected function _recv()
	{
		$data = fread($this->_fp, 24);
		$array = $this->_show_request($data);
		if ($array['bodylength']) {
			$bodylength = $array['bodylength'];
			$data = '';
			while ($bodylength > 0) {
				$recv_data = fread($this->_fp, $bodylength);
				$bodylength -= strlen($recv_data);
				$data .= $recv_data;
			}

			if ($array['extralength']) {
				$extra_unpacked = unpack('Nint', substr($data, 0, $array['extralength']));
				$array['extra'] = $extra_unpacked['int'];
			}
			$array['key'] = substr($data, $array['extralength'], $array['keylength']);
			$array['body'] = substr($data, $array['extralength'] + $array['keylength']);
		}
		return $array;
	}

	public function __construct()
	{
	}


	public function listMechanisms()
	{
		$this->_send(array('opcode' => 0x20));
		$data = $this->_recv();
		return explode(" ", $data['body']);
	}

	public function setSaslAuthData($user, $password)
	{
		$this->_send(array(
			'opcode' => 0x21,
			'key' => 'PLAIN',
			'value' => '' . chr(0) . $user . chr(0) . $password
		));
		$data = $this->_recv();

		if ($data['status']) {
			throw new Exception($data['body'], $data['status']);
		}
	}

	public function addServer($host, $port, $weight = 0)
	{
		$this->_fp = stream_socket_client($host . ':' . $port);
	}

	public function addServers($servers)
	{
		for ($i = 0; $i < count($servers); $i++) {
			$s = $servers[$i];
			if (count($s) >= 2) {
				$this->addServer($s[0], $s[1]);
			} else {
				trigger_error("could not add entry #"
					.($i+1)." to the server list", E_USER_WARNING);
			}
		}
	}

	public function addServersByString($servers)
	{
		$servers = explode(",", $servers);
		for ($i = 0; $i < count($servers); $i++) {
			$servers[$i] = explode(":", $servers[$i]);
		}
		$this->addServers($servers);
	}

	public function get($key)
	{
		$sent = $this->_send(array(
			'opcode' => 0x00,
			'key' => $key,
		));
		$data = $this->_recv();
		if (0 == $data['status']) {
			if ($data['extra'] & self::MEMC_VAL_COMPRESSED) {
				$body = gzuncompress($data['body']);
			} else {
				$body = $data['body'];
			}

			$type = $data['extra'] & self::MEMC_VAL_TYPE_MASK;

			switch ($type) {
				case self::MEMC_VAL_IS_STRING:
					$body = strval($body);
					break;

				case self::MEMC_VAL_IS_LONG:
					$body = intval($body);
					break;

				case self::MEMC_VAL_IS_DOUBLE:
					$body = doubleval($body);
					break;

				case self::MEMC_VAL_IS_BOOL:
					$body = $body ? true : false;
					break;

				case self::MEMC_VAL_IS_SERIALIZED:
					$body = unserialize($body);
					break;
			}

			return $body;
		}
		return FALSE;
	}

	/**
	 * process value and get flag
	 *
	 * @param int $flag
	 * @param mixed $value
	 * @access protected
	 * @return array($flag, $processed_value)
	 */
	protected function _processValue($flag, $value)
	{
		if (is_string($value)) {
			$flag |= self::MEMC_VAL_IS_STRING;
		} elseif (is_long($value)) {
			$flag |= self::MEMC_VAL_IS_LONG;
		} elseif (is_double($value)) {
			$flag |= self::MEMC_VAL_IS_DOUBLE;
		} elseif (is_bool($value)) {
			$flag |= self::MEMC_VAL_IS_BOOL;
		} else {
			$value = serialize($value);
			$flag |= self::MEMC_VAL_IS_SERIALIZED;
		}

		if (array_key_exists(self::OPT_COMPRESSION, $this->_options) and $this->_options[self::OPT_COMPRESSION]) {
			$flag |= self::MEMC_VAL_COMPRESSED;
			$value = gzcompress($value);
		}
		return array($flag, $value);
	}

	public function add($key, $value, $expiration = 0)
	{
		list($flag, $value) = $this->_processValue(0, $value);

		$extra = pack('NN', $flag, $expiration);
		$sent = $this->_send(array(
			'opcode' => 0x02,
			'key' => $key,
			'value' => $value,
			'extra' => $extra,
		));
		$data = $this->_recv();
		if ($data['status'] == 0) {
			return TRUE;
		}

		return FALSE;
	}

	public function set($key, $value, $expiration = 0)
	{
		list($flag, $value) = $this->_processValue(0, $value);

		$extra = pack('NN', $flag, $expiration);
		$sent = $this->_send(array(
			'opcode' => 0x01,
			'key' => $key,
			'value' => $value,
			'extra' => $extra,
		));
		$data = $this->_recv();
		if ($data['status'] == 0) {
			return TRUE;
		}

		return FALSE;
	}

	public function delete($key)
	{
		$sent = $this->_send(array(
			'opcode' => 0x04,
			'key' => $key,
		));
		$data = $this->_recv();
		if ($data['status'] == 0) {
			return TRUE;
		}

		return FALSE;
	}

	public function replace($key, $value, $expiration = 0)
	{
		list($flag, $value) = $this->_processValue(0, $value);

		$extra = pack('NN', $flag, $expiration);
		$sent = $this->_send(array(
			'opcode' => 0x03,
			'key' => $key,
			'value' => $value,
			'extra' => $extra,
		));
		$data = $this->_recv();
		if ($data['status'] == 0) {
			return TRUE;
		}

		return FALSE;
	}

	protected function _upper($num)
	{
		return $num << 32;
	}

	protected function _lower($num)
	{
		return $num % (2 << 32);
	}

	public function increment($key, $offset = 1, $expiration = 0)
	{
		$initial_value = 0;
		$extra = pack('N2N2N', $this->_upper($offset), $this->_lower($offset), $this->_upper($initial_value), $this->_lower($initial_value), $expiration);
		$sent = $this->_send(array(
			'opcode' => 0x05,
			'key' => $key,
			'extra' => $extra,
		));
		$data = $this->_recv();
		if ($data['status'] == 0) {
			return TRUE;
		}

		return FALSE;
	}

	public function decrement($key, $offset = 1, $expiration = 0)
	{
		$initial_value = 0;
		$extra = pack('N2N2N', $this->_upper($offset), $this->_lower($offset), $this->_upper($initial_value), $this->_lower($initial_value), $expiration);
		$sent = $this->_send(array(
			'opcode' => 0x06,
			'key' => $key,
			'extra' => $extra,
		));
		$data = $this->_recv();
		if ($data['status'] == 0) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Get statistics of the server
	 *
	 * @param string $type The type of statistics to fetch. Valid values are
	 *                     {reset, malloc, maps, cachedump, slabs, items,
	 *                     sizes}. According to the memcached protocol spec
	 *                     these additional arguments "are subject to change
	 *                     for the convenience of memcache developers".
	 *
	 * @link http://code.google.com/p/memcached/wiki/BinaryProtocolRevamped#Stat
	 * @access public
	 * @return array  Returns an associative array of server statistics or
	 *                FALSE on failure.
	 */
	public function getStats($type = null)
	{
		$this->_send(
			array(
				'opcode' => 0x10,
				'key' => $type,
			)
		);

		$ret = array();
		while (true) {
			$item = $this->_recv();
			if (empty($item['key'])) {
				break;
			}
			$ret[$item['key']] = $item['body'];
		}
		return $ret;
	}

	public function append($key, $value)
	{
		// TODO: If the Memcached::OPT_COMPRESSION is enabled, the operation
		// should failed.
		$sent = $this->_send(array(
			'opcode' => 0x0e,
			'key' => $key,
			'value' => $value,
		));
		$data = $this->_recv();
		if ($data['status'] == 0) {
			return TRUE;
		}

		return FALSE;
	}

	public function prepend($key, $value)
	{
		// TODO: If the Memcached::OPT_COMPRESSION is enabled, the operation
		// should failed.
		$sent = $this->_send(array(
			'opcode' => 0x0f,
			'key' => $key,
			'value' => $value,
		));
		$data = $this->_recv();
		if ($data['status'] == 0) {
			return TRUE;
		}

		return FALSE;
	}

	public function getMulti(array $keys)
	{
		// TODO: from http://code.google.com/p/memcached/wiki/BinaryProtocolRevamped#Get,_Get_Quietly,_Get_Key,_Get_Key_Quietly
		//       Clients should implement multi-get (still important for reducing network roundtrips!) as n pipelined requests ...
		$list = array();

		foreach ($keys as $key) {
			$value = $this->get($key);
			if (false !== $value) {
				$list[$key] = $value;
			}
		}

		return $list;
	}


	protected $_options = array();

	public function setOption($key, $value)
	{
		$this->_options[$key] = $value;
	}

	/**
	 * Set the memcache object to be a session handler
	 *
	 * Ex:
	 * $m = new MemcacheSASL;
	 * $m->addServer('xxx', 11211);
	 * $m->setSaslAuthData('user', 'password');
	 * $m->setSaveHandler();
	 * session_start();
	 * $_SESSION['hello'] = 'world';
	 *
	 * @access public
	 * @return void
	 */
	public function setSaveHandler()
	{
		session_set_save_handler(
			function($savePath, $sessionName){ // open
			},
			function(){ // close
			},
			function($sessionId){ // read
				return $this->get($sessionId);
			},
			function($sessionId, $data){ // write
				return $this->set($sessionId, $data);
			},
			function($sessionId){ // destroy
				$this->delete($sessionId);
			},
			function($lifetime) { // gc
			}
		);
	}
}

class Tattoo_Cache_MemcacheSASL extends Desire_Cache_Abstract
{
	private $memcache;
	private $isSupported = false;

	public function __construct()
	{
		$this->memcache = new MemcacheSASL;

		$configs = (array)Desire_Config::get('desire.cache.memcacheSASL', array());

		$successed = false;
		foreach ($configs['servers'] as $server) {
			if (call_user_func_array(array($this->memcache, 'addServer'), $server)) {
				$successed = true;
			}
		}

		$this->memcache->setSaslAuthData($configs['user'], $configs['password']);

		if ($successed) {
			$this->isSupported = true;
		} else {
			$this->isSupported = $this->memcache->set("Tattoo_Cache_MemcacheSASL.isSupported", true);
		}

	}

	/**
	 * Returns FALSE if memcached is not supported on the system.
	 * If it is, we setup the memcached object & return TRUE
	 */
	public function isSupported()
	{
		return $this->isSupported;
	}

	/**
	 * Add a memcached server to connection pool
	 *
	 * @param string $host Point to the host where memcached is listening for connections. This parameter may also specify other transports like unix:///path/to/memcached.sock to use UNIX domain sockets, in this case port must also be set to 0.
	 * @param int $port Point to the port where memcached is listening for connections. Set this parameter to 0 when using UNIX domain sockets.
	 * @param bool $persistent Controls the use of a persistent connection. Default to TRUE.
	 */
	public function addServer()
	{
		$config = func_get_args();
		return call_user_func_array(array($this->memcache, 'addServer'), $config);
	}

	/**
	 * Store the value in the memcache memory (overwrite if key exists)
	 *
	 * @param string $key The key that will be associated with the item.
	 * @param mixed $var The variable to store. Strings and integers are stored as is, other types are stored serialized.
	 * @param int $compress ignore
	 * @param int $expire Expiration time of the item. If it's equal to zero, the item will never expire. You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
	 * @return bool
	 */
	public function set($key, $var, $compress = 0, $expire = 0)
	{
		return $this->memcache->set($key, $var, $expire);
	}

	/**
	 * Stores variable var with key only if such key doesn't exist at the server yet.
	 *
	 * @param string $key The key that will be associated with the item.
	 * @param mixed $var The variable to store. Strings and integers are stored as is, other types are stored serialized.
	 * @param int $compress ignore
	 * @param int $expire Expiration time of the item. If it's equal to zero, the item will never expire. You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
	 * @return bool
	 */
	public function add($key, $var, $compress = 0, $expire = 0)
	{
		return $this->memcache->add($key, $var, $expire);
	}

	/**
	 * Replace value of the existing item.
	 *
	 * @param string $key The key that will be associated with the item.
	 * @param mixed $var The variable to store. Strings and integers are stored as is, other types are stored serialized.
	 * @param int $compress ignore
	 * @param int $expire Expiration time of the item. If it's equal to zero, the item will never expire. You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
	 * @return bool
	 */
	public function replace($key, $var, $compress = 0, $expire = 0)
	{
		return $this->memcache->replace($key, $var, $expire);
	}

	/**
	 * Increment item's value.
	 *
	 * @param string $key Key of the item to increment.
	 * @param int $value Increment the item by value.
	 * @return bool
	 */
	public function increment($key, $value = 1)
	{
		return $this->memcache->increment($key, intval($value));
	}

	/**
	 * Decrements value of the item by value.
	 *
	 * @param string $key Key of the item do decrement.
	 * @param int $value Decrement the item by value
	 * @return bool
	 */
	public function decrement($key, $value = 1)
	{
		return $this->memcache->decrement($key, intval($value));
	}

	/**
	 * Returns previously stored data if an item with such key exists on the server at this moment. You can pass array of keys to get array of values. The result array will contain only found key-value pairs.
	 *
	 * @param mixed $key The key or array of keys to fetch.
	 * @return mixed
	 */
	public function get($key)
	{
		return $this->memcache->get($key);
	}

	/**
	 * Delete item from the server
	 *
	 * @param string $key The key associated with the item to delete.
	 * @param int $timeout ignore
	 * @return bool
	 */
	public function delete($key, $timeout=0)
	{
		return $this->memcache->delete($key);
	}

	/**
	 * lock
	 * @param $key
	 * @return bool
	 */
	public function lock($key, $expire = 60)
	{
		if ($this->get($key)) {
			return false;
		}
		ignore_user_abort(true);
		return $this->set($key, true, 0, $expire);
	}

	/**
	 * unlock
	 * @param $key
	 * @return bool
	 */
	public function unlock($key)
	{
		if (!$this->get($key)) {
			return false;
		}
		return $this->delete($key);
	}

	/**
	 * Flush all existing items at the server
	 *
	 * @return void
	 */
	public function flush()
	{
		//ignore
	}
}