<?php

/**
 * Class Tattoo_Dubbo_Request
 */
class Tattoo_Dubbo_Request
{
	private $registry;
	private $serviceName;
	private $serviceVersion;
	private $serviceGroup;
	private $serviceToken;
	private $method;
	private $args;
	private $paramTypes;
	private $provider;

	public function __construct(array $args = array())
	{
		foreach($args as $k => $v) {
			$this->{$k} = $v;
		}

		$this->provider = $this->getProvider();
	}

	/**
	 * @return Tattoo_Dubbo_Registry_Abstract
	 */
	private function getProvider()
	{
		return $this->registry->getProvider(
			$this->serviceName,
			$this->serviceVersion,
			$this->serviceGroup,
			$this->serviceToken
		);
	}

	public function invoker()
	{
		return json_decode($this->invoke(), true);
	}

	public function invoke()
	{
		switch($this->provider['scheme']) {
			case 'httpx':
			case 'http':
				return $this->invokeViaHttp();
				break;
			default:
				return $this->invokeViaTelnet();
		}
	}

	protected function invokeViaHttp($tries = 0)
	{
		try {
			$url = sprintf("http://%s:%s%s",
				$this->provider['host'],
				$this->provider['port'],
				$this->provider['path'],
				$this->method
			);

			$data = json_encode($this->args);

			$header = array(
				'Host' => $this->provider['host'].':'.$this->provider['port'],
				"Method-Name" => $this->method,
				'Service-Version' => $this->serviceVersion,
				'Service-Group' => $this->serviceGroup,
				'Accept' => 'text/json',
				'Content-Type' => 'text/fastjson; charset=UTF-8',
			);

			//$this->paramTypes = array();
			$this->paramTypes && $header["Parameter-Types"] = implode(';', $this->paramTypes).";";

			try {
				$res = Desire_Http::post($url, $data, $header);
			} catch (Exception $e) {
				if ($tries == 3) throw $e;

				$this->provider = $this->getProvider();//reset provider
				return $this->invokeViaHttp(++$tries);
			}

			Desire_Logger::debug('-- Dubbo.invokeViaHttp --', array(__METHOD__, $url, $data, $header, $res));

			return $res->data;

		}
		catch (Exception $e) {
			Desire_Logger::warn("Caught Exception ('{$e->getMessage()}')");
		}
	}

	protected function invokeViaTelnet()
	{
		try {
			$t = new Tattoo_Telnet(array('telnet' => false,));
			$t->connect(array(
				'host' => $this->provider['host'],
				'port' => $this->provider['port'],
			));

			array_walk($this->args, function(&$item1){
				$item1 = json_encode($item1);
			});

			$invoke = sprintf('invoke %s.%s(%s)',
				$this->provider['config']['interface'],
				$this->method,
				implode(',', $this->args)
			);

			$t->println($invoke);
			$t->read_stream();
			$response = explode("\n", $t->get_data());
			$response = substr(implode("\n", array_splice($response, 1, -2)), 1, -1);

			$t->disconnect();

			return $response;
		}
		catch (Exception $e) {
			Desire_Logger::warn("Caught Exception ('{$e->getMessage()}')");
		}
	}
}