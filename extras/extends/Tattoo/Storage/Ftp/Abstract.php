<?php
/**
 * A Distributed Storage class with ftp
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Abstract.php 488 2014-10-14 10:03:34Z alacner $
 */

abstract class Tattoo_Storage_Ftp_Abstract
{
	protected $configs = array();

	public function __construct()
	{
		$this->configs = $this->loadConfiguration();
	}

	public function getGroupConfig()
	{
		$groups = array();
		foreach ($this->configs as $config) {
			if ($config['is_closed']) continue;
			$groups[] = $config;
		}
		if (empty($groups)) return array();
		shuffle($groups);
		return end($groups);
	}

	public function upload($file)
	{
		clearstatcache();
		if (!file_exists($file)) return false;

		$group = $this->getGroupConfig();
		if (empty($group)) {
			throw new Exception('Unavailable Group Servers');
		};

		// GroupId/年/月日/文件大小%62/内容的hash.后缀
		$groupId = $this->toStPad($group['group_id'], 2);
		$year = $this->toStPad(date('G'));
		$day = $this->toStPad(date('nd'), 2);

		$mod62 = $this->toStPad(Desire_Time::locale() % 62);
		$hash = md5_file($file);
		$suffix = strrchr($file, '.');

		$remotePath = sprintf('%s/%s/%s/%s', $groupId, $year, $day, $mod62);
		$remoteFile = sprintf('%s/%s.%s', $remotePath, $hash, $suffix);

		//TODO if failed
		foreach ($group['server'] as $server) {
			$resource = new Tattoo_Storage_Common_FtpClient($server);
			$resource->mkdir($remotePath);
			$ret = $resource->ftp_put($remoteFile, $file, FTP_BINARY);
			$resource->ftp_close();
		}
		return $remoteFile;
	}

	public function toStPad($number, $padLength = 1)
	{
		return str_pad(Desire_Math::decst($number), $padLength, '0', STR_PAD_LEFT);
	}

	/**
	 * @abstract
	 * @return array(array('host' => 'localhost'[, 'ssl' => true, 'port' => 21, 'timeout' => 90]),...);
	 */
	abstract protected function loadConfiguration();
}