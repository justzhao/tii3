<?php

class _TopApiMethod
{
	private $methodName = '';
	private $apiParas = array();

	public function __construct($methodName, array $apiParas = array())
	{
		$this->methodName = $methodName;
		$this->apiParas = $apiParas;
	}

	public function getApiMethodName()
	{
		return $this->methodName;
	}

	public function getApiParas()
	{
		return $this->apiParas;
	}
}

class TopClient
{
	public $appkey;

	public $secretKey;

	public $gatewayUrl = "http://gw.api.taobao.com/router/rest";

	public $format = "xml";

	protected $signMethod = "md5";

	protected $apiVersion = "2.0";

	protected $sdkVersion = "top-sdk-php-20110518";

	public $runtime = array();

	protected function generateSign($params)
	{
		ksort($params);

		$stringToBeSigned = $this->secretKey;
		foreach ($params as $k => $v)
		{
			if("@" != substr($v, 0, 1))
			{
				$stringToBeSigned .= "$k$v";
			}
		}
		unset($k, $v);
		$stringToBeSigned .= $this->secretKey;

		return strtoupper(md5($stringToBeSigned));
	}

	protected function _curl($url, $postFields = null)
	{
		$headers = array(
			'Referer' => $url,
		);

		if (is_array($postFields) && 0 < count($postFields))
		{
			$postFiles = array();

			foreach ($postFields as $k => $v)
			{
				if("@" === substr($v, 0, 1))//判断是不是文件上传
				{
					$postFiles[$k] = substr($v, 1);
					unset($postFields[$k]);
				}
			}

			$response = Desire_Http::post($url, array($postFields, $postFiles), $headers);
		}
		else {
			$response = Desire_Http::get($url, $headers);
		}

		return $response;
	}

	public function curl($url, $postFields = null)
	{
		$response = $this->_curl($url, $postFields);
		//print_r($response);
		if (200 !== $response->state)
		{
			throw new Exception($response->message, $response->state);
		}

		$this->runtime = $response->runtime;

		return $response->data;
	}

	protected function logCommunicationError($apiName, $requestUrl, $errorCode, $responseTxt)
	{
		/*
		$logData = array(
			date("Y-m-d H:i:s"),
			$apiName,
			$this->appkey,
			$localIp,
			PHP_OS,
			$this->sdkVersion,
			$requestUrl,
			$errorCode,
			str_replace("\n","",$responseTxt)
		);
		*/
	}

	protected function logBizError($resp)
	{
		/*
		$logData = array(
			date("Y-m-d H:i:s"),
			$resp
		);
		*/
	}

	/**
	 * From xml object to array, and fixed list data.
	 */
	protected function toArray($data)
	{
		if (is_object($data)) $data = get_object_vars($data);
		if (isset($data['@attributes']['list']) && $data['@attributes']['list'])
		{
			unset($data['@attributes']);
			list($key, $value) = each($data);
			if (isset($value['0']))
			{
				$data = array($key => $value);
			}
			else {
				$data = array($key => array($value));
			}
		}

		return is_array($data) ? array_map(array($this, 'toArray'), $data) : $data;
	}

	public function execute($request, $session = null)
	{
		//组装系统参数
		$sysParams["app_key"] = $this->appkey;
		$sysParams["v"] = $this->apiVersion;
		$sysParams["format"] = $this->format;
		$sysParams["sign_method"] = $this->signMethod;
		$sysParams["method"] = $request->getApiMethodName();
		$sysParams["timestamp"] = date("Y-m-d H:i:s");
		$sysParams["partner_id"] = $this->sdkVersion;
		if (null != $session)
		{
			$sysParams["session"] = $session;
		}

		//获取业务参数
		$apiParams = $request->getApiParas();

		//签名
		$sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams));

		//系统参数放入GET请求串
		$requestUrl = $this->gatewayUrl . "?";
		foreach ($sysParams as $sysParamKey => $sysParamValue)
		{
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
		$requestUrl = substr($requestUrl, 0, -1);

		//发起HTTP请求
		try
		{
			$resp = $this->curl($requestUrl, $apiParams);
		}
		catch (Exception $e)
		{
			$this->logCommunicationError($sysParams["method"],$requestUrl,"HTTP_ERROR_" . $e->getCode(),$e->getMessage());
			return false;
		}

		//解析TOP返回结果
		$respWellFormed = false;
		if ("json" == $this->format)
		{
			$respObject = json_decode($resp, true);

			if (null !== $respObject)
			{
				$respWellFormed = true;
			}
		}
		else if("xml" == $this->format)
		{
			$respObject = @simplexml_load_string($resp);

			if (false !== $respObject)
			{
				$respWellFormed = true;

				$respObject = $this->toArray($respObject);
			}
		}

		//返回的HTTP文本不是标准JSON或者XML，记下错误日志
		if (false === $respWellFormed)
		{
			$this->logCommunicationError($sysParams["method"],$requestUrl,"HTTP_RESPONSE_NOT_WELL_FORMED",$resp);
			return false;
		}

		//如果TOP返回了错误码，记录到业务错误日志中
		if (isset($respObject['code']))
		{
			$this->logBizError($resp);
		}

		return $respObject;
	}

	/**
	 * 缺省方法
	 * 替换top接口方法中的.为_
	 * TopClient::getInstance()->taobao_user_get(array(应用级输入参数));
	 */
	public function __call($functionName, $arguments) {
		if (strpos($functionName, '_') === false) return false;
		isset($arguments[0]) || $arguments[0] = array();
		$arguments[0] = new _TopApiMethod(str_replace('_', '.', $functionName), $arguments[0]);
		return call_user_func_array(array($this, 'execute'), $arguments);
	}
}