<?php
/**
 * This is the PHP for dubbo 2.5.x
 *
 * More information is available at http://dubbo.io
 * 
 * @author  Alacner Zhang <alacner@gmail.com>
 * @version $Id: Zookeeper.php 6463 2016-08-11 15:18:28Z alacner $
 */

//Make sure PHP has support for Zookeeper
//@see https://github.com/andreiz/php-zookeeper
if (!class_exists('Zookeeper')) {
	throw new Desire_Exception('Require Zookeeper extension loaded');
}

/**
 * Class Tattoo_Dubbo_Registry_Zookeeper
 * @cacheNamespace Tattoo_Dubbo_Registry_Zookeeper
 */
class Tattoo_Dubbo_Registry_Zookeeper extends Tattoo_Dubbo_Registry_Abstract
{
	protected $address = array();

	public function __construct($parseUrl)
	{
		parent::__construct($parseUrl);

		$this->address[] = $parseUrl['host'] . ':' . $parseUrl['port'];
		if ($parseUrl['query']) {
			parse_str($parseUrl['query'], $get);
			if (isset($get['backup'])) {
				$this->address[] = $get['backup'];
			}
		}
	}

	/**
	 * 获取zookeeper
	 *
	 * @param int $retry
	 * @return resource|Zookeeper
	 * @throws Desire_Exception
	 */
	protected function getZookeeper($retry = 3)
	{
		if ($retry < 0) {
			throw new Desire_Exception('Without a valid Zookeeper handle via address: %s', json_encode($this->address));
		}

		foreach($this->address as $address) {
			list($h, $p) = explode(":", $address, 2);
			$fp = @fsockopen($h, $p, $errno, $errstr, 1);
			if (!$fp) {
				continue;
			} else {
				fclose($fp);
				return Desire::object('Zookeeper', $address);
			}
		}

		sleep(1);
		return $this->getZookeeper(--$retry);
	}

	protected function getProviders($serviceName)
	{
		return $this->getZookeeper()->getChildren('/dubbo/'.$serviceName.'/providers');
	}

	protected function getConfigurators($serviceName)
	{
		return $this->getZookeeper()->getChildren('/dubbo/'.$serviceName.'/configurators');
	}

	public function setProvider($serviceName, $url)
	{
		$params = array(
			array(
				'perms' => Zookeeper::PERM_ALL,
				'scheme' => 'world',
				'id'    => 'anyone'
			)
		);

		$path = '/dubbo/'.$serviceName;

		if (!$this->getZookeeper()->exists($path)) {
			$this->getZookeeper()->create($path, null, $params);
		}

		foreach(array('configurators', 'consumers', 'routers', 'providers') as $p) {
			if (!$this->getZookeeper()->exists($path.'/'.$p)) {
				$this->getZookeeper()->create($path.'/'.$p, null, $params);
			}
		}

		$path = '/dubbo/'.$serviceName.'/providers/' . urlencode($url);
		if ($this->getZookeeper()->exists($path)) {
			$this->getZookeeper()->delete($path);
		}
		return $this->getZookeeper()->create($path, null, $params, Zookeeper::EPHEMERAL);
	}
}