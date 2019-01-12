<?php
/**
 * For those who dont want to deal with handling the connection once created,
 * here is a simple class that allows you to call any ftp function as if it were an extended method.
 * It automatically puts the ftp connection into the first argument slot (as all ftp functions require)..
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: FtpClient.php 488 2014-10-14 10:03:34Z alacner $
 */
class Tattoo_Storage_Common_FtpClient
{
	protected $config = array(
		'host' => 'localhost',
		'port' => 21,
		'timeout' => 90,
		'password' => '',
		'ssl' => false,
		'username' => 'anonymous',
		'password' => '',
		'base_path' => '',
	);

	protected $resource = null;

	public function __construct(array $config = array())
	{
		$this->config = array_merge($this->config, $config);
		$this->resource = call_user_func_array(
			$this->config['ssl'] ? 'ftp_ssl_connect' : 'ftp_connect',
			array(
				$this->config['host'],
				$this->config['port'],
				$this->config['timeout'],
			)
		);

		if (!$this->resource) {
			throw new Exception("Couldn't connect to {$this->config['host']}");
		}

		$isLogin = $this->resource->ftp_login($this->config['username'], $this->config['password']);
		if (!$isLogin) {
			throw new Exception("Couldn't connect as {$this->config['username']}");
		}
	}

	public function mkdir($path)
	{
		$dirs = explode("/", $path);
		$path = $this->config['base_path'];
		$ret = true;

		for ($i = 0, $j = count($dirs); $i < $j; $i++) {
			$path .= "/" . $dirs[$i];
			if (!@$this->resource->ftp_chdir($path)) {
				@$this->resource->ftp_chdir("/");
				if (!@$this->resource->ftp_mkdir($path)) {
					$ret = false;
					break;
				}
			}
		}
		return $ret;
	}

	public function __call($func, $a)
	{
		if (strstr($func, 'ftp_') !== false && function_exists($func)) {
			array_unshift($a, $this->resource);
			return call_user_func_array($func, $a);
		} else {
			// replace with your own error handler.
			throw new Exception("$func is not a valid FTP function");
		}
	}
}
