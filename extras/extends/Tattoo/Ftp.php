<?php
/**
 * Ftp class
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Ftp.php 6416 2016-08-08 09:24:35Z alacner $
 */

final class Tattoo_Ftp
{
	protected $config = [
		'host' => 'localhost',
		'port' => 21,
		'timeout' => 90,
		'password' => '',
		'ssl' => false,
		'username' => 'anonymous',
		'password' => '',
	];

	protected $resource = null;

	public function __construct(array $config = [])
	{
		$this->config = array_merge($this->config, $config);
		$this->resource = call_user_func_array(
			$this->config['ssl'] ? 'ftp_ssl_connect' : 'ftp_connect',
			[
				$this->config['host'],
				$this->config['port'],
				$this->config['timeout'],
			]
		);

		if (!$this->resource) {
			throw new Exception("Couldn't connect to {$this->config['host']}");
		}

		@ftp_set_option($this->resource, FTP_TIMEOUT_SEC, $this->config['timeout']);

		if (!@ftp_login($this->resource, $this->config['username'], $this->config['password'])) {
			throw new Exception("Couldn't connect as {$this->config['username']}");
		}
	}

	/**
	 * Creates a directory
	 */
	public function mkdir($path)
	{
		if (empty($path)) return false;
		$dir = explode("/", $path);
		$path = $this->ftp_pwd() . '/';
		$ret = true;
		for ($i=0; $i<count($dir); $i++) {
			$path = $path . $dir[$i] . '/';
			if (!@$this->ftp_chdir($path)) {
				if (!@$this->ftp_mkdir($dir[$i])) {
					$ret = false;
					break;
				}
			}
			@$this->ftp_chdir($path);
		}
		if (!$ret) return false;
		return true;
	}

	/**
	 * Upload local file
	 */
	public function upload($localFile, $remoteFile)
	{
		if (empty($localFile) || empty($remoteFile)) return false;
		$ftppath = dirname($remoteFile);
		if (!empty($ftppath)) {
			$this->mkdir($ftppath);
			@$this->ftp_chdir($ftppath);
			$remoteFile = basename($remoteFile);
		}
		$ret = $this->ftp_nb_put($remoteFile, $localFile, FTP_BINARY);
		while ($ret == FTP_MOREDATA) {
			$ret = $this->ftp_nb_continue();
		}
		if ($ret != FTP_FINISHED) return false;
		return true;
	}

	/**
	 * Download remote file
	 */
	public function download($localFile, $remoteFile)
	{
		if (empty($localFile) || empty($remoteFile)) return false;
		$ret = $this->ftp_nb_get($localFile, $remoteFile, FTP_BINARY);
		while ($ret == FTP_MOREDATA) {
			$ret = $this->ftp_nb_continue();
		}
		if ($ret != FTP_FINISHED) return false;
		return true;
	}

	/**
	 * Call ftp_* function, automatic injection ftp_*($this->resource, ...);
	 */
	public function __call($name, $arguments)
	{
		if (strstr($name, 'ftp_') !== false && function_exists($name)) {
			array_unshift($arguments, $this->resource);
			return call_user_func_array($name, $arguments);
		} else {
			// replace with your own error handler.
			throw new Exception("$name is not a valid FTP function");
		}
	}
}