<?php
/**
 * dubbo 服务的注册中心抽象类
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Abstract.php 6463 2016-08-11 15:18:28Z alacner $
 */

abstract class Tattoo_Dubbo_Registry_Abstract
{
	protected $parseUrl = array();

	public function __construct($parseUrl)
	{
		$this->parseUrl = $parseUrl;
	}

	abstract protected function getProviders($serviceName);
	abstract protected function getConfigurators($serviceName);

	public function getParseUrl()
	{
		return $this->parseUrl;
	}

	public function getProtocol()
	{
		return $this->parseUrl['scheme'];
	}

	protected function parse($urls)
	{
		$res = array();
		foreach($urls as $url) {
			$parseUrl = parse_url(urldecode($url));
			parse_str($parseUrl['query'], $config);
			$parseUrl['config'] = $config;

			$res[sprintf('%s:%s', $parseUrl['host'], $parseUrl['port'])] = $parseUrl;
		}
		return $res;
	}

	/**
	 * 获取服务
	 *
	 * @expired 60
	 * @cacheName provider.{0}.{1}.{2}.{3}
	 *
	 * @param $serviceName
	 * @param string $version
	 * @param string $group
	 * @param string $token
	 * @return mixed
	 *
	 * @throws Desire_Exception
	 */
	public function getProvider($serviceName, $version = '', $group = '', $token = '')
	{
		$version = $version ?: '0.0.0';
		$group = explode(',', $group ?: '*');
		$token = $token ?: '';

		$providers = new SplPriorityQueue();
		$configures = $this->parse($this->getConfigurators($serviceName));
		foreach($this->parse($this->getProviders($serviceName)) as $hostname => $provider) {
			//disabled
			if (isset($configures[$hostname]['disabled']) && $configures[$hostname]['disabled']) {
				continue;
			}
			//version
			$_version = isset($provider['config']['version']) ? $provider['config']['version'] : '0.0.0';
			if ($_version != $version) continue;
			//group
			if (!in_array('*', $group)) {
				$_group = isset($provider['config']['group']) ? $provider['config']['group'] : '*';
				if (!in_array($_group, $group)) continue;
			}

			$_token = isset($provider['config']['token']) ? $provider['config']['token'] : '';
			if ($_token != $token) continue;

			$provider['port'] = $provider['port'] ?: 80;

			$providers->insert($provider, in_array($provider['scheme'], array('http', 'httpx')) ? mt_rand(1000, 2000) : mt_rand(100, 200));
		}

		if ($providers->isEmpty()) {
			throw new Desire_Exception("No provider for `%s' service", $serviceName);
		}

		return $providers->top();
	}

	abstract public function setProvider($serviceName, $url);
}