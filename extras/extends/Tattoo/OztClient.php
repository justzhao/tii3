<?php

class _Tattoo_OztService
{
	protected $client;/** @var Tattoo_OztClient */
	protected $serviceName;
	protected $gatewayUrl;

	public function __construct($client, $serviceName)
	{
		$this->client = $client;
		$this->serviceName = $serviceName;
		$this->gatewayUrl = rtrim($this->client->getGatewayUrl(), '/');
	}

	public function __call($methodName, $arguments) {
		$url = sprintf('%s/%s.%s',
			$this->gatewayUrl,
			$this->serviceName,
			$methodName
		);

		$params = array();
		if ($arguments) {
			list($params, $crypts) = $arguments;

			if (!is_array($params)) {
				throw new Desire_Exception("First argument must be a array");
			}

			if (is_array($crypts)) {
				$encryptFields = array();
				foreach($crypts as $field) {
					if (isset($params[$field])) {
						$encryptFields[] = $field;
						$params[$field] = $this->encode($params[$field]);
					}
				}
				$params['encrypted_fields'] = $encryptFields;
			}

			foreach($params as $k => $v) {
				$params[$k] = json_encode($v);
			}
		}

		$params['appkey'] = $this->client->getAppkey();
		$params['timestamp'] = Desire_Time::now();
		$params['nonce'] = Desire_Math::uniqId(Desire_Math::random(55));
		$params['signature'] = Desire_Math::hashArr($this->client->getSecretKey(), $params['timestamp'], $params['nonce']);

		$response = Desire_Http::post($url, array($params), $this->client->getHeaders());

		if ($response->state != 200) {
			throw new Desire_Exception('call: %s.%s error', $this->serviceName, $methodName);
		}

		$data = json_decode($response->data, true);
		//print_r($response->data);
		if (is_array($data) && is_array($data['encrypted_fields'])){
			foreach($data['encrypted_fields'] as $f) {
				if (isset($data[$f])) {
					$data[$f] = $this->decode($data[$f]);
				}
			}
		}

		return $data;
	}

	protected function encode($data)
	{
		return Desire_Security_Encryption::encode(json_encode($data), 300, $this->client->getSecretKey());
	}

	protected function decode($data)
	{
		$data = Desire_Security_Encryption::decode($data, $this->client->getSecretKey());
		return json_decode($data, true);
	}
}

class Tattoo_OztClient
{
	protected $appkey;
	protected $secretKey;
	protected $gatewayUrl = "http://api.gw.oztcdn.com";
	protected $headers = array();
	protected $services = array();

	public function __construct($appkey = null, $secretKey = null)
	{
		if ($appkey) {
			$this->appkey = $appkey;
			$this->secretKey = $secretKey;
		} else {
			$this->appkey = Desire_Config::get('tattoo.ozt.client.appkey', $this->appkey);
			$this->secretKey = Desire_Config::get('tattoo.ozt.client.secretKey', $this->secretKey);
		}

		if (empty($this->appkey) || empty($this->secretKey)) {
			throw new Desire_Exception("You need to set up the appkey and secretKey first");
		}

		$this->gatewayUrl = Desire_Config::get('tattoo.ozt.client.gatewayUrl', $this->gatewayUrl);
		$this->headers = Desire_Config::get('tattoo.ozt.client.headers', $this->headers);
	}

	public function getAppkey()
	{
		return $this->appkey;
	}

	public function getSecretKey()
	{
		return $this->secretKey;
	}

	public function getGatewayUrl()
	{
		return $this->gatewayUrl;
	}

	public function getHeaders()
	{
		return $this->headers;
	}

	public function setHeader($name, $value, $replace = false)
	{
		if ($replace) {
			$this->header[$name] = $value;
		} else {
			if (!isset($this->header[$name])) {
				$this->header[$name] = $value;
			} else {
				if (is_array($this->header[$name])) {
					$this->header[$name][] = $value;
				} else {
					$this->header[$name] = array($this->header[$name], $value);
				}
			}
		}
	}

	public function setHeaders($headers)
	{
		$this->headers = $headers;
	}

	public function getService($serviceName)
	{
		if (!isset($this->services[$serviceName])) {
			$this->services[$serviceName] = new _Tattoo_OztService($this, $serviceName);
		}
		return $this->services[$serviceName];
	}
}