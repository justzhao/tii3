<?php

class CheetahMailClient
{
	protected $name;
	protected $cleartext;
	
	protected $services = array(
		'login1' => 'https://ebm.cheetahmail.com/api/login1',
		'ebmtrigger1' => 'https://ebm.cheetahmail.com/ebm/ebmtrigger1',
		'bulkmail1' => 'https://app.cheetahmail.com/api/bulkmail1',
		'load1' => 'https://app.cheetahmail.com/cgi-bin/api/load1',
		'mailgo1' => 'https://app.cheetahmail.com/cgi-bin/api/mailgo1',
		'setlist1' => 'https://ebm.cheetahmail.com/cgi-bin/api/setlist1',
	);
	
	protected $cookie = null;
	
	protected $sdkVersion = "ebm-sdk-php-20111011";
	
	protected function getServiceURI($name) {
		if (!isset($this->services[$name])) {
			throw new Exception("Unsupported service name: " . $name);
		}
		return $this->services[$name];
	}
	
	protected function _curl($url, $postFields = null, $headers = array())
	{
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
	
	protected function login1() {
		$requestUrl = $this->getServiceURI('login1');
		$response = $this->_curl($requestUrl, array(
			'name' => $this->name,
			'cleartext' => $this->cleartext,
		));
		
		if (200 !== $response->state) {
			$this->logCommunicationError('login1', $requestUrl, "HTTP_ERROR_" . $response->state, $response->message);
			return false;
		}
		$this->cookie = $response->headers['set-cookie'];
		return trim($response->data) == 'OK' ? true : false;
	}
	
	protected function curl($url, $postFields = null, $headers = array())
	{
		$response = $this->_curl($url, $postFields, $headers);
		
		if (200 !== $response->state)
		{
			throw new Exception($response->message, $response->state);
		}
		
		return $response->data;
	}

	protected function logCommunicationError($apiName, $requestUrl, $errorCode, $responseTxt)
	{
		/*
		$logData = array(
			date("Y-m-d H:i:s"),
			$apiName,
			$this->name,
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
	
	public function execute($name, $apiParams = array())
	{
		//发起HTTP请求
		try
		{
			$this->cookie || $this->login1();
			$requestUrl = $this->getServiceURI($name);
			$resp = $this->curl($requestUrl, $apiParams, array('Cookie' => $this->cookie));
		}
		catch (Exception $e)
		{
			$this->logCommunicationError($name, $requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
			return false;
		}
		
		return trim($resp) == 'OK' ? true : false;
	}
}