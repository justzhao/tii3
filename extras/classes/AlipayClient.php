<?php

class _AlipayApiMethod
{
	private $methodName = '';
	private $apiParas = array();

	public function __construct($methodName, $apiParas = array())
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

class AlipayClient
{
	protected $partnerId;//Partner ID
	protected $secretKey;//安全校验码
	protected $gatewayUrl = "https://mapi.alipay.com/gateway.do";
	//public $gatewayUrl = "http://notify.alipay.com/trade/notify_query.do";
	protected $format = "json";
	protected $signType = "MD5";
	protected $apiVersion = "3.1";
	protected $inputCharset = 'utf-8';
	protected $sdkVersion = "alipay-sdk-php-20110821";

	public function __construct($partnerId, $secretKey, $gatewayUrl = null, $format = 'json')
	{
		$this->partnerId = $partnerId;
		$this->secretKey = $secretKey;
		if ($gatewayUrl) $this->gatewayUrl = $gatewayUrl;
		$this->format = $format;
	}

	public function generateSign($params)
	{
		ksort($params);
		reset($params);
		
		$toBeSigned = array();
		foreach ($params as $k => $v) {
			if (in_array($k, array('sign', 'sign_type')) || empty($v)) continue;
			if ("@" != substr($v, 0, 1)) {
				$toBeSigned[] = "$k=$v";
			}
		}
		unset($k, $v);
		$stringToBeSigned = implode('&', $toBeSigned);
		$stringToBeSigned .= $this->secretKey;
		
		return md5($stringToBeSigned);
	}
	
	public function checkSign($params, $sign)
	{
		return $this->generateSign($params) === $sign ? true : false;
	}
	
	protected function _curl($url, $postFields = null)
	{
		if (is_array($postFields) && 0 < count($postFields)) {
			$postFiles = array();
			
			foreach ($postFields as $k => $v) {
				if("@" === substr($v, 0, 1)) {//判断是不是文件上传
					$postFiles[$k] = substr($v, 1);
					unset($postFields[$k]);
				}
			}

			$response = Desire_Http::post($url, array($postFields, $postFiles));
		}
		else {
			$response = Desire_Http::get($url);
		}
		
		return $response;
	}
	
	protected function curl($url, $postFields = null)
	{
		$response = $this->_curl($url, $postFields);

		if (200 !== $response->state) {
			throw new Exception($response->message, $response->state);
		}
		
		return $response->data;
	}
	
	protected function logCommunicationError($apiName, $requestUrl, $errorCode, $responseTxt)
	{
		Desire_Logger::err('logCommunicationError', array(
				date("Y-m-d H:i:s"),
				$apiName,
				$this->partnerId,
				//$localIp,
				PHP_OS,
				$this->sdkVersion,
				$requestUrl,
				$errorCode,
				str_replace("\n","",$responseTxt)
			)
		);
	}
	
	protected function logBizError($resp)
	{
		Desire_Logger::err('logBizError', array(
				date("Y-m-d H:i:s"),
				$resp
			)
		);
	}
	
	public function execute($request, $callback = false)
	{
		//组装系统参数
		$sysParams["partner"] = $this->partnerId;
		$sysParams["_input_charset"] = $this->inputCharset;
		$sysParams["sign_type"] = $this->signType;
		$sysParams["service"] = $request->getApiMethodName();
		
		//获取业务参数
		$apiParams = $request->getApiParas();

		//签名
		$sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams));

		//系统参数放入GET请求串
		$requestUrl = $this->gatewayUrl . "?";
		
		if ($callback) {
			return $requestUrl . http_build_query(array_merge($apiParams, $sysParams));
		}
		
		$requestUrl .= http_build_query($sysParams);
		
		//发起HTTP请求
		try {
			$resp = $this->curl($requestUrl, $apiParams);
		}
		catch (Exception $e) {
			$this->logCommunicationError($sysParams["method"],$requestUrl,"HTTP_ERROR_" . $e->getCode(),$e->getMessage());
			return false;
		}
		
		
		//解析TOP返回结果
		$respWellFormed = false;
		if ("json" == $this->format) {
			$respObject = json_decode($resp);
			if (null !== $respObject) {
				$respWellFormed = true;
				foreach ($respObject as $propKey => $propValue) {
					$respObject = $propValue;
				}
			}
		} else if("xml" == $this->format) {
			$respObject = @simplexml_load_string($resp);
			if (false !== $respObject) {
				$respWellFormed = true;
			}
		}

		//返回的HTTP文本不是标准JSON或者XML，记下错误日志
		if (false === $respWellFormed) {
			$this->logCommunicationError($sysParams["method"], $requestUrl, "HTTP_RESPONSE_NOT_WELL_FORMED", $resp);
			return false;
		}

		//如果TOP返回了错误码，记录到业务错误日志中
		if (isset($respObject->code)) {
			$this->logBizError($resp);
		}
		return $respObject;
	}

	/**
	 * 返回支付宝登录URL
	 *
	 * @param string $callbackUrl
	 * @return string
	 */
	public function getLoginUrl($callbackUrl, $email = null)
	{
		return $this->user_authentication(array(
				'return_url' => $callbackUrl,
				'email' => $email,
			),
			true
		);
	}

	/**
	 * From object to array.
	 */
	protected function toArray($data)
	{
		if (is_object($data)) $data = get_object_vars($data);
		return is_array($data) ? array_map(array($this, 'toArray'), $data) : $data;
	}

	/**
	 * 缺省方法
	 * 替换alipay接口方法中的.为_
	 * AlipayClient::getInstance()->user_authentication(array(应用级输入参数));
	 */
	public function __call($functionName, $arguments)
	{
		if (strpos($functionName, '_') === false) return false;
		list($namespace,) = explode("_", $functionName, 2);
		if (in_array($namespace, ['koubei', 'alipay'])) {
			$functionName = str_replace('_', '.', $functionName);
		}
		$arguments[0] = new _AlipayApiMethod($functionName, $arguments[0]);
		$data = call_user_func_array(array($this, 'execute'), $arguments);
		return is_object($data) ? $this->toArray($data) : $data;
	}
}