<?php
/**
 * socket abstract
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Abstract.php 6463 2016-08-11 15:18:28Z alacner $
 */

abstract class Tattoo_Socket_Abstract
{
	protected $address = '127.0.0.1';
	protected $port = 0;
	protected $domain = AF_INET;
	protected $type = SOCK_STREAM;
	protected $protocol = SOL_TCP;
	/**
	 * Socket handle
	 *
	 * @var Resource|NULL
	 */
	protected $socket = NULL;

	/**
	 * Constructor
	 *
	 * @param string $address Default localhost
	 * @param integer $port Default 1206
	 */

	/**
	 * @param string $address
	 * @param int $port
	 * @param int $domain AF_INET[IPv4],AF_INET6[IPv6],AF_UNIX
	 * @param int $type SOCK_STREAM,SOCK_SEQPACKET
	 * @param int $protocol
	 * @param int $backlog
	 */
	public function __construct($address = '127.0.0.1', $port = 0, $domain = AF_INET, $type = SOCK_STREAM, $protocol = SOL_TCP)
	{
		$this->address = $address;
		$this->port = $port ?: 1206;
		$this->domain = $domain;
		$this->type = $type;
		$this->protocol = $protocol;

		set_time_limit(0);

		if (!$this->socket = socket_create($this->domain, $this->type, $this->protocol)) {
			throw new Desire_Exception("socket_create() failed: %s", $this->getStrerror());
		}

		if (!socket_bind($this->socket, $this->address, $this->port)) {
			throw new Desire_Exception("socket_bind() failed: %s", $this->getStrerror());
		}
	}

	/**
	 * @return resource
	 * @throws Desire_Exception
	 */
	public function  getSocket()
	{
		if (is_resource($this->socket)) {
			return $this->socket;
		} else {
			throw new Desire_Exception("socket is not resource");
		}
	}

	public function getStrerror()
	{
		return socket_strerror(socket_last_error());
	}

	public function  __destroy()
	{
		socket_close($this->socket);
	}

	/**
	 * To string (for debug logging)
	 *
	 * @return string
	 */
	public function __toString()
	{
		return "{$this->address}:{$this->port}";
	}
}