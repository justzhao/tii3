<?php

class _DziInvokeBuilder
{
	private $client;
	private $accessToken = null;

	public function __construct(Tattoo_Dzi_Client $client, $accessToken = null)
	{
		$this->client = $client;
		$this->accessToken = $accessToken;
	}

	/**
	 * _InvokeBuilder::->some_method(array(应用级输入参数));
	 */
	public function __call($functionName, $arguments = array())
	{
		$fs =  explode('_', $functionName, 3);
		isset($fs[2]) && $fs[2] = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', ($fs[2])))));
		$url = $this->client->gateway . "/" .implode('/', $fs);

		isset($arguments[0]) || $arguments[0] = array();
		if ($this->accessToken) {
			$arguments[0]['access_token'] = $this->accessToken;
		}

		$response = Desire_Http::post($url, array($arguments[0]), $this->client->headers, array('timeout' => '3600'));
		Desire_Logger::debug(__METHOD__, $response);
		if ($response->state === 200) {
			return json_decode($response->data, true);
		} else {
			throw new Desire_Exception($response->message);
		}
	}
}

class Tattoo_Dzi_Client
{
	public $gateway;
	public $appkey;
	public $secretKey;
	public $headers = array();

	public function __construct()
	{
		$config = Desire_Config::get('tattoo.dzi.client');
		if (empty($config) || !isset($config['gateway']) || !isset($config['appkey']) || !isset($config['secret_key'])) {
			throw new Desire_Exception("not found tattoo.dzi.client with [gateway,appkey,secret_key] in tattoo.config.php");
		}
		$this->gateway = substr($config['gateway'], -1) == '/' ? substr($config['gateway'], 0, -1) : $config['gateway'];
		$this->appkey = $config['appkey'];
		$this->secretKey = $config['secret_key'];
	}


	public function getAccessTokenWithLogin($username = '', $passwd = '', $ip = '')
	{
		return $this->getAccessToken(array(
			'username' => $username,
			'passwd' => $passwd,
			'ip' => $ip,
		));
	}

	public function getAccessToken($params = array())
	{
		$_params = $params;

		$params = array_merge($params, array(
			'timestamp' => Desire_Time::now(),
			'nonce' => Desire_Math::random(64).Desire_Time::micro(),
		));

		$tmpArr = array($this->secretKey, $params['timestamp'], $params['nonce']);
		sort($tmpArr, SORT_STRING); // use SORT_STRING rule
		$params['signature'] = sha1(implode($tmpArr));
		$params['appkey'] = $this->appkey;

		$invoker = new _DziInvokeBuilder($this);
		$accessTokenInfo = $invoker->token($params);

		if (isset($params['username'])) {
			$cacheId = $this->getCacheKey($accessTokenInfo['account']['id']);
			$this->getCache()->set(
				$this->getParamCacheKey($accessTokenInfo['account']['id']),
				Desire_Security_Encryption::encode(json_encode($_params)),
				86400
			);
		} else {
			$cacheId = $this->getCacheKey(0);
		}

		$accessTokenInfo['dzi_expire_time'] = Desire_Time::now() + $accessTokenInfo['expires_in'];
		$this->getCache()->set(
			$cacheId,
			json_encode($accessTokenInfo),
			$accessTokenInfo['expires_in']
		);

		return $accessTokenInfo;
	}

	/**
	 * @param int $accountId
	 * @return _DziInvokeBuilder
	 * @throws Desire_Exception
	 */
	public function channel($accountId = 0)
	{
		$accessTokenInfo = $this->getCache()->get($this->getCacheKey($accountId));
		if ($accessTokenInfo) {
			$accessTokenInfo = json_decode($accessTokenInfo, true);
			$invoker = new _DziInvokeBuilder($this, $accessTokenInfo['access_token']);
			$accessTokenInfo = $invoker->token_refresh();
			if (!isset($accessTokenInfo['errcode'])) {
				return $invoker;
			}
		}

		$paramsInfo = $this->getCache()->get($this->getParamCacheKey($accountId));

		if ($paramsInfo) {
			$params = json_decode(Desire_Security_Encryption::decode($paramsInfo), true);
			$accessTokenInfo = $this->getAccessToken($params);
			$invoker = new _DziInvokeBuilder($this, $accessTokenInfo['access_token']);
			return $invoker;
		} else {
			if ($accountId) {
				throw new Desire_Exception("you should login again");
			} else {
				$accessTokenInfo = $this->getAccessToken();
				$invoker = new _DziInvokeBuilder($this, $accessTokenInfo['access_token']);
				return $invoker;
			}
		}
	}

	/**
	 * @return Desire_Cache
	 */
	private function getCache()
	{
		return Desire::object("Desire_Cache");
	}

	private function getCacheKey($accountId = 0)
	{
		return 'dzi.client.' . $this->appkey . '.' . intval($accountId);
	}

	private function getParamCacheKey($accountId = 0)
	{
		return 'dzi.client.param.' . $this->appkey . '.' . intval($accountId);
	}
}