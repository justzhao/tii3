<?php
/**
 * PHP memcached client class
 *
 * For build develop environment in windows using memcached.
 *
 * @package     memcached-client
 * @copyright   Copyright 2013-2014, Fwolf
 * @license     http://opensource.org/licenses/mit-license MIT
 * @version     1.2.0
 */
require_once __DIR__ . '/memcached-client.php';

class Memcache
{
	protected $servers = array();


	/**
	 * Socket connect handle
	 *
	 * Point to last successful connect, ignore others
	 * @var resource
	 */
	protected $memcached = null;

	public function __construct($debug = false, $compress_threshold = 10240, $persistant = false) {

		$this->memcached = new memcached();

		$this->memcached->set_debug($debug);
		$this->memcached->set_compress_threshold($compress_threshold);
		$this->memcached->set_persistant($persistant);
		$this->memcached->set_debug($debug);
	}

	/**
	 * Add a serer to the server pool
	 *
	 * @param   string  $host
	 * @param   int     $port
	 * @param   int     $weight
	 * @return  boolean
	 */
	public function addServer($host, $port = 11211, $weight = 0)
	{
		$this->servers[] = $host.':'.$port;
		$this->memcached->set_servers($this->servers);
		return true;
	}


	/**
	 * Add multiple servers to the server pool
	 *
	 * @param   array   $servers
	 * @return  boolean
	 */
	public function addServers($servers)
	{
		foreach ((array)$servers as $svr) {
			$host = array_shift($svr);

			$port = array_shift($svr);
			if (is_null($port)) {
				$port = 11211;
			}

			$weight = array_shift($svr);
			if (is_null($weight)) {
				$weight = 0;
			}

			$this->addServer($host, $port, $weight);
		}

		return true;
	}

	/**
	 * Delete an item
	 *
	 * @param   string  $key
	 * @param   int     $timeout
	 * @return  boolean
	 */
	public function delete($key, $timeout = 0)
	{
		return $this->memcached->delete($key, $timeout);
	}


	/**
	 * Retrieve an item
	 *
	 * @param   string|array  $key
	 * @return  mixed
	 */
	public function get($key)
	{
		return is_array($key) ? $this->memcached->get_multi($key) : $this->memcached->get($key);
	}

	public function set($key, $val, $flags = 0, $expt = 0)
	{
		return $this->memcached->set($key, $val, $expt);
	}

	public function add($key, $val, $flags = 0, $expt = 0)
	{
		return $this->memcached->add($key, $val, $expt);
	}

	public function replace($key, $val, $flags = 0, $expt = 0)
	{
		return $this->memcached->replace($key, $val, $expt);
	}

	public function increment($key, $value = 1)
	{
		return $this->memcached->incr($key, $value);
	}

	public function decrement($key, $value = 1)
	{
		return $this->memcached->decr($key, $value);
	}

	public function flush()
	{
		return false;
	}

	public function getExtendedStats($type = null, $slabid = null, $limit = 100)
	{
		return false;
	}
}