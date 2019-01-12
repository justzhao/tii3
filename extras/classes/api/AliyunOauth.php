<?php
/**
 * 阿里云云账户平台提供的API
 *
 * @author Xie Jin <xiejin@alibaba-inc.com>
 * @author Alacner <zhihua.zhangzh@alibaba-inc.com>
 * @copyright Copyright &copy; 2003-2011 phpwind.com
 * @see https://account.aliyun.com/developer/oauth.jsp
 * @license
 */
class AliyunOauthApi {
	//protected $auisUrl = 'http://10.250.4.49:54800';
	protected $auisUrl = 'https://account.aliyun.com';
	protected $appKey;
	protected $secretKey;
	protected $sdkVersion = "aliyun-oauth-sdk-php-20111223";


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

	protected function curl($url, $postFields = null)
	{
		$response = $this->_curl($url, $postFields);

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

	/***
	 * 用户登录，第一步request_token
	 * @param $callback
	 * return array or false
	 */
	public function requestToken($callback) {
		$params = array('oauth_callback'=>$callback);
		$data = $this->auisResult('/oauth/request_token', $params, '', 'POST');
		//dump($data);
		parse_str($data, $output);
		if (count($output) < 2) {
			return false;
		}
		return $output;
	}
	
	/***
	 * 用户登录，第二步access_token
	 * @param $auth_token
	 * @param $auth_token_secret
	 * return array or false
	 */
	public function accessToken($auth_token, $auth_verifier, $auth_token_secret) {
		$params = array('oauth_token' => $auth_token, 'oauth_verifier' => $auth_verifier);
		$data = $this->auisResult('/oauth/access_token', $params, $auth_token_secret, 'POST');
		parse_str($data, $output);
		if (count($output) < 2) {
			return false;
		}
		return $output;
	}
	
	/***
	 * 根据accessToken得到用户登录信息
	 * @param $oauth_token
	 * @param $oauth_token_secret
	 * @return array or false
	 */
	public function getProfile($oauth_token, $oauth_token_secret) {
		$params = array('oauth_token' => $oauth_token);
		return $this->auisResult2('/openapi/id/load', $params, $oauth_token_secret, 'GET');
	}
	
	/**
	 * 获取用户信息
	 * @param $oauth_token
	 * @param $oauth_token_secret
	 * @return
	 */
	public function getYunidAndKp($oauth_token, $oauth_token_secret) {
		$params = array('oauth_token' => $oauth_token);
		return $this->auisResult2('/openapi/id/aliyunid_kp', $params, $oauth_token_secret, 'GET');
	}
	
	/***
	 * 根据oauth_token获得pk_id
	 * @param $oauth_token
	 * @param $oauth_token_secret
	 * @return false or obj
	 *
	 */
	public function getKp($oauth_token, $oauth_token_secret) {
		$params = array('oauth_token' => $oauth_token);
		return $this->auisResult2('/openapi/id/kp', $params, $oauth_token_secret, 'GET');
	}
	
	/***
	 * 根据oauth_token获得user_mobile
	 * @param $oauth_token
	 * @param $oauth_token_secret
	 * @return false or obj
	 *
	 */
	public function getMobile($oauth_token, $oauth_token_secret) {
		$params = array('oauth_token' => $oauth_token);
		return $this->auisResult2('/openapi/id/mobile_number', $params, $oauth_token_secret, 'GET');
	}
	/***
	 * 根据用户oauth_token得到用户名信息
	 * @param $oauth_token
	 * @param $oauth_token_secret
	 * @return false or obj
	 *
	 */
	public function getUserNameByOauth($oauth_token, $oauth_token_secret) {
		$params = array('oauth_token' => $oauth_token);
		return $this->auisResult2('/openapi/id/aliyunid', $params, $oauth_token_secret, 'POST');
	}

	/***
	 * 修改密码
	 * @param $user_name
	 * @param $oldPassword
	 * @param $password
	 * @return false or obj
	 *
	 */
	public function updatePassword($user_name, $oldPassword, $password) {
		$keys = $this->access_token_xauth($user_name, $oldPassword);
		if (!$keys) return false;
		$params = array('oauth_token' => $keys['oauth_token'], 'oldPassword' => $oldPassword, 'password' => $password);
		$result = $this->auisResult2('/openapi/id/pass/update', $params, $keys['oauth_token_secret'], 'POST');
		return is_array($result) ? true : false;
	}

	/***
	 * 内部接口：根据用户kp得到用户名信息
	 * @param $kp
	 * @return false or obj
	 *
	 */
	public function getUserNameByKp($kp) {
		$params = array('kp' => $kp);
		return $this->auisResult2('/innerapi/id/aliyunid_by_kp', $params, '', 'GET');
	}
	
	/***
	 * 内部接口：根据cookie中的ticket取accessToken & tokenSecret
	 * @params $cookie_ticket
	 * @return array or false
	 */
	public function accessTokenByTicket($ticket_token) {
		$params = array('oauth_ticket' => $ticket_token);
		$data = $this->auisResult('/innerapi/oauth/access_token_by_ticket', $params, '', 'POST');
		
		parse_str($data, $output);
		if (count($output) < 2) {
			return false;
		}
		return $output;
	
	}
	
	/***
	 * 内部接口：根据用户云账号得到账号的所有信息
	 * @param $user_name
	 * @return false or obj
	 *
	 */
	public function loadUser($user_name) {
		$params = array('aliyunID' => $user_name);
		return $this->auisResult2('/innerapi/id/load_by_aliyunid', $params, '', 'POST');
	}

	/***
	 * 内部接口：创建一个用户
	 * @param $oauth_token
	 * @param $oauth_token_secret
	 * @return false or obj
	 *
	 */
	public function apiRegisterID($user_name, $user_passwd) {
		$params = array('aliyunID' => $user_name, 'password' => $user_passwd);
		return $this->auisResult2('/innerapi/id/create', $params, '', 'POST');
	}

	/***
	 * 内部接口：验证用户密码
	 * @param $user_name
	 * @param $user_passwd
	 * @return false or obj
	 *
	 */
	public function access_token_xauth($user_name, $user_passwd) {
		$params = array('xauth_aliyunid' => $user_name, 'xauth_password' => $user_passwd);
		$data = $this->auisResult('/innerapi/oauth/access_token_xauth', $params, '', 'POST');

		parse_str($data, $output);
		if (count($output) < 2) {
			return false;
		}
		return $output;
	}
	/**
	 * 绑定taobao
	 * @param unknown_type $oauth_token
	 * @param unknown_type $taobaoId
	 * @param unknown_type $password
	 * @return
	 */
	public function bindTaobao($oauth_token,$oauth_token_secret,$taobaoId,$password) {
		$params = array('oauth_token' => $oauth_token, 'taobaoid' => $taobaoId, 'taobaoPassword' => $password);
		return $this->auisResult2('/openapi/sp/taobao/set_taobaoid', $params, $oauth_token_secret, 'GET');
	}
	/**
	 * 获取用户的淘宝绑定id
	 * @param string $oauth_token
	 * @return array
	 */
	public function taobaoID($oauth_token,$oauth_token_secret) {
		$params = array('oauth_token' => $oauth_token);
		$return_data = $this->auisResult2('/openapi/sp/taobao/taobaoid', $params, $oauth_token_secret, 'GET');
		return isset($return_data['taobaoAccount']) ? $return_data['taobaoAccount'] : false;
	}
	/**
	 * 获取淘宝免登地址
	 * @param string $oauth_token
	 * @param string $callbaceUrl
	 * @return
	 */
	public function taobaoTrustUrl($oauth_token, $oauth_token_secret, $callbaceUrl) {
		$params = array('oauth_token' => $oauth_token, 'url' => urlencode($callbaceUrl));
		$return_data = $this->auisResult2('/openapi/sp/taobao/taobao_trust_url', $params, $oauth_token_secret, 'GET');
		return isset($return_data['trustUrl']) ? $return_data['trustUrl'] : false;
	}
	
	/***
	 *参数加密方法
	 *
	 */
	public function signature($params, $url, $key, $method = 'GET') {
		ksort($params);
		$queryString = '';
		foreach ($params as $k => $v) {
			$queryString .= urlencode($k) . '=' . (strpos($v, 'http')===false ? urlencode($v) : $v) . '&';
		}
		$queryString = trim($queryString, '&');
		$base_string = urlencode($method) . '&' . urlencode($url) . '&' . urlencode($queryString);
		//error_log($base_string."\r\n",3,BASE_PATH.'data/oauth.log');
		$oauth_signature = base64_encode(hash_hmac('sha1', $base_string, $key, true));
		//error_log($oauth_signature."\r\n",3,BASE_PATH.'data/oauth.log');
		return $oauth_signature;
	}
	
	/**
	 * 返回 auis 请求结果
	 * @param string $interface
	 * @param array $param
	 */
	protected function auisResult($url, $arguments, $oauth_token_secret = '', $method = 'GET') {
		$params = array(
			'oauth_consumer_key' => $this->appKey,
			'oauth_nonce' => $this->_getUuid(),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_version' => '1.0'
		);
		$params = array_merge($params, $arguments);
		$url = $this->auisUrl . $url;
		$secret_key = $this->secretKey."&";
		if (! empty($oauth_token_secret)) {
			$secret_key .= $oauth_token_secret;
		}
		$sign = $this->signature($params, $url, $secret_key, $method);
		$params['oauth_signature'] = $sign;

		//发起HTTP请求
		$resp = '';
		try
		{
			if (strtoupper($method) == 'POST') {
				$resp = $this->curl($url, $params);
			} else {
				$url = $url . '?' . http_build_query($params); //var_dump($url);
				$resp = $this->curl($url);
			}
		}
		catch (Exception $e)
		{
			$this->logCommunicationError($method, $url, "HTTP_ERROR_" . $e->getCode(),$e->getMessage());
			return false;
		}

		return $resp;
	}

	protected function auisResult2($url, $arguments, $oauth_token_secret = '', $method = 'GET') {
		$resp = $this->auisResult($url, $arguments, $oauth_token_secret, $method);
		$respObject = json_decode($resp, true);
		//如果返回了错误码，记录到业务错误日志中
		if (isset($respObject['errorCode']))
		{
			$this->logBizError($resp);
			if ($respObject['errorCode'] == 40003) {
				return true;
			}
			return false;
		}
		return $respObject;
	}

	protected function _getUuid($prefix = '', $adddate = true) {
		$uniqid = uniqid(mt_rand(10, 99));
		if ($adddate) {
			$uniqid = date ( "ymdhis" ) . substr ( $uniqid, - 7 );
		}
		if ($prefix) $uniqid = $prefix . $uniqid;
		return $uniqid;
   }

}