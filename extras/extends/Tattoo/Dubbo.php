<?php
/**
 * This is the PHP for dubbo 2.5.x
 *
 * More information is available at http://dubbo.io
 * 
 * @author  Alacner Zhang <alacner@gmail.com>
 * @version $Id: Dubbo.php 6463 2016-08-11 15:18:28Z alacner $
 */

class Tattoo_Dubbo
{
	private $application = array();
	private $registry;
	private $config = array();

	/**
	 * @param $applicationName
	 * @param $config
	 */
	public function __construct($applicationName = null, $config = NULL)
	{
		$this->config = $config ?: Desire_Config::get('tattoo.dubbo', array());

		$this->setRegistry($this->getConfig('registry.address', 'zookeeper://127.0.0.1:2181?backup=127.0.0.1:2181'));
		if ($applicationName) {
			$this->application['name'] = $applicationName;
		};
	}

	/**
	 * 设置应用名
	 *
	 * @param $name
	 */
	public function setApplication($name)
	{
		$this->application['name'] = $name;
	}

	/**
	 * 设置注册中心地址
	 *
	 * @param $address protocol: multicast,zookeeper,redis,dubbo
	 * @throws Desire_Exception
	 */
	public function setRegistry($address)
	{
		$parseUrl = parse_url($address);
		switch($parseUrl['scheme']) {
			case 'zookeeper':
				$this->registry = Desire::object('@Tattoo_Dubbo_Registry_Zookeeper', $parseUrl);
				break;
			default:
				throw new Desire_Exception("No registry handler for `%s' protocol", $parseUrl['scheme']);
		}
	}

	/**
	 * 获取服务提供者
	 *
	 * @param $serviceName
	 * @param string $serviceVersion
	 * @param string $serviceGroup
	 * @param string $serviceToken
	 * @return Tattoo_Dubbo_Provider
	 * @throws Desire_Exception
	 */
	public function getProvider($serviceName, $serviceVersion = '', $serviceGroup = '', $serviceToken = '')
	{
		if (!$this->registry instanceof Desire_Delegate) {
			throw new Desire_Exception("No registry handler");
		}
		return Desire::object('Tattoo_Dubbo_Provider', array(
			'registry' => $this->registry,
			'serviceName' => $serviceName,
			'serviceVersion' => $serviceVersion,
			'serviceGroup' => $serviceGroup,
			'serviceToken' => $serviceToken,
		));
	}

	/**
	 * 暴露服务
	 *
	 * @param $serviceName
	 * @param $hostname
	 * @param $port
	 * @param string $methods
	 * @throws Desire_Exception
	 */
	public function setProvider($serviceName, $hostname, $port, $methods = '')
	{
		if (!$this->registry instanceof Desire_Delegate) {
			throw new Desire_Exception("No registry handler");
		}
		if (!isset($this->application['name'])) {
			throw new Desire_Exception("Not set application name");
		}
		$url = sprintf('httpx://%s:%s/dubbo/%s?anyhost=true&application=%s&interface=%s&methods=%s&side=provider&timestamp=%s',
			$hostname, $port, $serviceName, $this->application['name'], $serviceName, $methods, ceil(Desire_Time::micro()*1000)
		);

		$this->registry->setProvider($serviceName, $url);
	}

	/**
	 * 获取配置信息
	 *
	 * @param $name
	 * @param null $default
	 * @return mixed|null
	 */
	public function getConfig($name, $default = null)
	{
		return Desire::getter($this->config, $name, $default);
	}
}
