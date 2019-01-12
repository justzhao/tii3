<?php

/**
 * 被动回复用户消息的各消息类型需要的XML数据包结构
 * @see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140543&token=&lang=zh_CN
 */
class Weixin_Transmit
{
	protected $object;

	public function __construct($object)
	{
		$this->object = $object;
	}

	private function fieldBuilder($fields = [])
	{
		$lines = [];

		if (is_array($fields)) {
			foreach($fields as $field => $value) {
				$lines[] = is_numeric($field) ? "<item>" : sprintf('<%s>', $field);

				if (is_array($value)) {
					$lines[] = sprintf("\n%s\n", $this->fieldBuilder($value));
				} else {
					$lines[] = is_numeric($value) ? $value : sprintf('<![CDATA[%s]]>', $value);
				}

				$lines[] = is_numeric($field) ? "</item>" : sprintf('</%s>', $field);
			}
		} else {
			$lines[] = is_numeric($fields) ? $fields : sprintf('<![CDATA[%s]]>', $fields);
		}

		return implode("", $lines);
	}

	public function builder($msgType, $fields = [])
	{
		$content = sprintf("<xml>
          <ToUserName><![CDATA[%s]]></ToUserName>
          <FromUserName><![CDATA[%s]]></FromUserName>
          <CreateTime>%s</CreateTime>
          <MsgType><![CDATA[%s]]></MsgType>
          %s
        </xml>",
			$this->object->FromUserName,
			$this->object->ToUserName,
			Desire_Time::now(),
			$msgType,
			$this->fieldBuilder($fields)
		);

		Desire_Logger::debug(__METHOD__, $content);

		return $content;
	}

	/**
	 * 回复文本消息
	 *
	 * @param 回复的消息内容（换行：在content中能够换行，微信客户端就支持换行显示）
	 * @see sprintf
	 * @return string
	 */
	public function text()
	{
		return $this->builder('text', ['Content' => call_user_func_array('sprintf', func_get_args())]);
	}

	/**
	 * 回复图片消息
	 *
	 * @param string $mediaId 通过素材管理中的接口上传多媒体文件，得到的id。
	 * @return string
	 */
	public function image($mediaId)
	{
		return $this->builder('image', ['Image' => ['MediaId' => $mediaId]]);
	}

	/**
	 * 回复语音消息
	 *
	 * @param string $mediaId 通过素材管理中的接口上传多媒体文件，得到的id。
	 * @return string
	 */
	public function voice($mediaId)
	{
		return $this->builder('voice', ['Voice' => ['MediaId' => $mediaId]]);
	}

	/**
	 * 回复视频消息
	 *
	 * @param $mediaId 通过素材管理中的接口上传多媒体文件，得到的id
	 * @param array $fields [
	 *   Title => 视频消息的标题
	 *   Description => 视频消息的描述
	 * ]
	 * @return string
	 */
	public function video($mediaId, array $fields = [])
	{
		$fields['MediaId'] = $mediaId;
		return $this->builder('video', ['Video' => $fields]);
	}

	/**
	 * 回复音乐消息
	 *
	 * @param $thumbMediaId 缩略图的媒体id，通过素材管理中的接口上传多媒体文件，得到的id
	 * @param array $fields [
	 *   Title => 音乐标题
	 *   Description => 音乐描述
	 *   MusicUrl => 音乐链接
	 *   HQMusicUrl => 高质量音乐链接，WIFI环境优先使用该链接播放音乐
	 * ]
	 * @return string
	 */
	public function music($thumbMediaId, array $fields = [])
	{
		$fields['ThumbMediaId'] = $thumbMediaId;
		return $this->builder('music', ['Music' => $fields]);
	}

	/**
	 * @param array $articles [
	 *   Title => 图文消息标题
	 *   Description => 图文消息描述
	 *   PicUrl => 图片链接，支持JPG、PNG格式，较好的效果为大图360*200，小图200*200
	 *   Url => 点击图文消息跳转链接
	 * ]
	 * @return string
	 * @throws Desire_Exception
	 */
	public function news(array $articles)
	{
		$fields['ArticleCount'] = count($articles);
		if ($fields['ArticleCount'] > 10) {
			throw new Desire_Exception('图文消息个数，限制为10条以内');
		}
		$fields['Articles'] = $articles;
		return $this->builder('news', $fields);
	}
}

/**
 * 根据接收的消息进行转换成返回数据
 * @see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140453&token=&lang=zh_CN
 */
abstract class Weixin_AnalyzerAbstract
{
	protected $object;
	protected $transmit;

	public function __construct($data)
	{
		if (empty($data)) throw new Desire_Exception("invalid data");
		$this->object = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
		$this->transmit = new Weixin_Transmit($this->object);
	}

	public function __toString()
	{
		return $this->result();
	}

	public function result()
	{
		return call_user_func([$this, trim($this->object->MsgType)]);
	}

	/**
	 * 接收文本消息
	 *
	 * <xml>
	 * <ToUserName><![CDATA[toUser]]></ToUserName>
	 * <FromUserName><![CDATA[fromUser]]></FromUserName>
	 * <CreateTime>1348831860</CreateTime>
	 * <MsgType><![CDATA[text]]></MsgType>
	 * <Content><![CDATA[this is a test]]></Content>
	 * <MsgId>1234567890123456</MsgId>
	 * </xml>
	 */
	abstract protected function text();

	/**
	 * 接收图片
	 *
	 * <xml>
	 * <ToUserName><![CDATA[toUser]]></ToUserName>
	 * <FromUserName><![CDATA[fromUser]]></FromUserName>
	 * <CreateTime>1348831860</CreateTime>
	 * <MsgType><![CDATA[image]]></MsgType>
	 * <PicUrl><![CDATA[this is a url]]></PicUrl>
	 * <MediaId><![CDATA[media_id]]></MediaId>
	 * <MsgId>1234567890123456</MsgId>
	 * </xml>
	 */
	abstract protected function image();

	/**
	 * 位置消息
	 *
	 * <xml>
	 * <ToUserName><![CDATA[toUser]]></ToUserName>
	 * <FromUserName><![CDATA[fromUser]]></FromUserName>
	 * <CreateTime>1351776360</CreateTime>
	 * <MsgType><![CDATA[location]]></MsgType>
	 * <Location_X>23.134521</Location_X> -- 地理位置维度
	 * <Location_Y>113.358803</Location_Y> -- 地理位置经度
	 * <Scale>20</Scale> -- 地图缩放大小
	 * <Label><![CDATA[位置信息]]></Label> -- 地理位置信息
	 * <MsgId>1234567890123456</MsgId>
	 * </xml>
	 */
	abstract protected function location();

	/**
	 * 接收语音
	 *
	 * <xml>
	 * <ToUserName><![CDATA[toUser]]></ToUserName>
	 * <FromUserName><![CDATA[fromUser]]></FromUserName>
	 * <CreateTime>1357290913</CreateTime>
	 * <MsgType><![CDATA[voice]]></MsgType>
	 * <MediaId><![CDATA[media_id]]></MediaId>
	 * <Format><![CDATA[Format]]></Format>
	 * <Recognition><![CDATA[腾讯微信团队]]></Recognition> -- 语音识别结果，UTF8编码
	 * <MsgId>1234567890123456</MsgId>
	 * </xml>
	 */
	abstract protected function voice();

	/**
	 * 接收视频
	 *
	 * <xml>
	 * <ToUserName><![CDATA[toUser]]></ToUserName>
	 * <FromUserName><![CDATA[fromUser]]></FromUserName>
	 * <CreateTime>1357290913</CreateTime>
	 * <MsgType><![CDATA[video]]></MsgType>
	 * <MediaId><![CDATA[media_id]]></MediaId>
	 * <ThumbMediaId><![CDATA[thumb_media_id]]></ThumbMediaId>
	 * <MsgId>1234567890123456</MsgId>
	 * </xml>
	 */
	abstract protected function video();

	/**
	 * 小视频消息
	 *
	 * <xml>
	 * <ToUserName><![CDATA[toUser]]></ToUserName>
	 * <FromUserName><![CDATA[fromUser]]></FromUserName>
	 * <CreateTime>1357290913</CreateTime>
	 * <MsgType><![CDATA[shortvideo]]></MsgType>
	 * <MediaId><![CDATA[media_id]]></MediaId>
	 * <ThumbMediaId><![CDATA[thumb_media_id]]></ThumbMediaId>
	 * <MsgId>1234567890123456</MsgId>
	 * </xml>
	 */
	abstract protected function shortvideo();

	/**
	 * 链接消息
	 *
	 * <xml>
	 * <ToUserName><![CDATA[toUser]]></ToUserName>
	 * <FromUserName><![CDATA[fromUser]]></FromUserName>
	 * <CreateTime>1351776360</CreateTime>
	 * <MsgType><![CDATA[link]]></MsgType>
	 * <Title><![CDATA[公众平台官网链接]]></Title>
	 * <Description><![CDATA[公众平台官网链接]]></Description>
	 * <Url><![CDATA[url]]></Url>
	 * <MsgId>1234567890123456</MsgId>
	 * </xml>
	 */
	abstract protected function link();

	/**
	 * 接收事件，关注等
	 * @see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140454&token=&lang=zh_CN
	 *
	 * <xml>
	 * <ToUserName><![CDATA[toUser]]></ToUserName>
	 * <FromUserName><![CDATA[FromUser]]></FromUserName>
	 * <CreateTime>123456789</CreateTime>
	 * <MsgType><![CDATA[event]]></MsgType>
	 * <Event><![CDATA[subscribe]]></Event>
	 * </xml>
	 */
	protected function event()
	{
		return call_user_func([$this, 'event_' . trim($this->object->Event)]);//继承类实现名称为event_{Event}的函数
	}
}

/**
 * Class Weixin_AnalyzerExample
 */
class Weixin_AnalyzerExample extends Weixin_AnalyzerAbstract
{
	protected function text()
	{
		$contentStr = trim($this->object->Content);
		if (is_array($contentStr)) {
			return $this->transmit->news($contentStr);
		} else{
			return $this->transmit->text($contentStr);
		}
	}

	protected function image()
	{
		return $this->transmit->text("你发送的是图片，地址为：%s", $this->object->PicUrl);
	}

	protected function voice()
	{
		return $this->transmit->text("你发送的是语音，媒体ID为：%s", $this->object->MediaId);
	}

	protected function video()
	{
		return $this->transmit->text("你发送的是视频，媒体ID为：%s", $this->object->MediaId);
	}

	protected function shortvideo()
	{
		return $this->transmit->text("你发送的是小视频，媒体ID为：%s", $this->object->MediaId);
	}

	protected function location()
	{
		return $this->transmit->text("你发送的是位置，纬度为：%s；经度为：%s；缩放级别为：%s；位置为：%s",
			$this->object->Location_X, $this->object->Location_Y, $this->object->Scale, $this->object->Label
		);
	}

	protected function link()
	{
		return $this->transmit->text("你发送的是链接，标题为：%s；内容为：%s；链接地址为：%s",
			$this->object->Title, $this->object->Description, $this->object->Url
		);
	}

	protected function event_subscribe()
	{
		return $this->transmit->text("你关注了我");
	}

	protected function event_unsubscribe()
	{
		return $this->transmit->text("你对我取消关注了");
	}

	/**
	 * 点击菜单消息
	 */
	protected function event_CLICK()
	{
		switch ($this->object->EventKey)
		{
			case "CLICK_HELP":
				$contentStr = "我能为你做些什么？";
				break;

			default:
				$contentStr = "你点击了菜单: ".$this->object->EventKey;
				break;
		}

		return $this->transmit->text($contentStr);
	}
}

/**
 * Class Weixin_Client
 *
 * $client = new Weixin_Client('appkey', 'secretKey');
 * print_r($client->getAccessToken());
 * print_r($client->media_upload([
 *     'access_token' => $client->getAccessToken(),
 *     'type' => 'image',
 *     'media' => '@/path/to/upload/filename.png'
 * ]));
 */
class Weixin_Client
{
	protected $appkey;
	protected $secretKey;
	protected $gatewayUrl = "https://api.weixin.qq.com/cgi-bin/";
	protected $runtime = [];

	/**
	 * @param $appkey
	 * @param $secretKey
	 */
	public function __construct($appkey, $secretKey)
	{
		$this->appkey = $appkey;
		$this->secretKey = $secretKey;
	}

	private function _curl($url, $postFields = null)
	{
		if (is_array($postFields) && 0 < count($postFields)) {
			$postFiles = [];

			foreach ($postFields as $k => $v) {
				if("@" === substr($v, 0, 1)) {//判断是不是文件上传
					$postFiles[$k] = substr($v, 1);
					unset($postFields[$k]);
				}
			}

			return Desire_Http::post($url, [$postFields, $postFiles]);
		} else {
			return Desire_Http::get($url);
		}
	}

	protected function curl($url, $postFields = null)
	{
		$response = $this->_curl($url, $postFields);
		//print_r($response);
		if (200 !== $response->state) {
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
	 * @param $method 接口路径，如：/material/add_news
	 * @param array $postFields POST提交的参数，如果是文件，用@开头表示
	 * @return bool|mixed
	 */
	public function execute($method, $postFields = [])
	{
		$requestUrl = $this->gatewayUrl . ltrim($method, '/');

		try {
			$resp = $this->curl($requestUrl, $postFields);
		} catch (Exception $e) {
			$this->logCommunicationError($method, $requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
			return false;
		}

		$respObject = json_decode($resp, true);

		//返回的HTTP文本不是标准JSON，记下错误日志
		if (empty($respObject)) {
			$this->logCommunicationError($method, $requestUrl, "HTTP_RESPONSE_NOT_WELL_FORMED", $resp);
			return false;
		}

		//如果返回了错误码，记录到业务错误日志中
		if (intval($respObject['errcode']) !== 0) {//errcode:0, errmsg:ok
			$this->logBizError($resp);
		}

		return $respObject;
	}

	/**
	 * 缺省方法
	 *
	 * 替换接口路径中的/为_，如果原来是_需要增加一个_，如：
	 * 1) /user/info/updateremark => user_info_updateremark
	 * 2) /material/add_news =>  material_add__news
	 * $instance->material_add__news($params = [], $postFields = []);
	 */
	public function __call($name, $arguments)
	{
		array_unshift($arguments, str_replace('//', '_', str_replace('_', '/', $name)));
		return call_user_func_array(array($this, 'execute'), $arguments);
	}

	/**
	 * 获取access token
	 *
	 * @cacheName getAccessToken.{appkey}
	 * @expired 7000
	 * @cacheMode cache
	 */
	public function getAccessToken()
	{
		return $this->token([
			'grant_type' => 'client_credential',
			'appid' => $this->appkey,
			'secret' => $this->secretKey,
		])['access_token'];
	}

	/**
	 * get ticket
	 *
	 * @cacheName getTicket.{0}
	 * @expired 7000
	 * @cacheMode cache
	 */
	public function getTicket($accessToken)
	{
		return $this->ticket_getticket([
			'type' => 'jsapi',
			'access_token' => $accessToken,
		])['ticket'];
	}

	/**
	 * 获取JsApi的一些配置信息，来自 JSSDK
	 *
	 * @param $ticket
	 * @param $url
	 * @return array
	 */
	public function getJsApiConfig($ticket, $url)
	{
		$timestamp = time();
		$nonceStr = Desire_Math::random(16);

		//这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket=$ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

		return [
			"appId" => $this->appkey,
			"nonceStr" => $nonceStr,
			"timestamp" => $timestamp,
			"url" => $url,
			"signature" => sha1($string),
			"rawString" => $string,
		];
	}
}

/**
 * Class Weixin_Enterprise Client
 */
class Weixin_EClient extends Weixin_Client
{
	protected $gatewayUrl = "https://qyapi.weixin.qq.com/cgi-bin/";

	/**
	 * @cacheName getAccessToken.{appkey}
	 * @expired 7000
	 * @cacheMode cache
	 */
	public function getAccessToken()
	{
		return $this->gettoken([
			'corpid' => $this->appkey,
			'corpsecret' => $this->secretKey,
		])['access_token'];
	}

	/**
	 * get ticket
	 *
	 * @cacheName getTicket.{0}
	 * @expired 7000
	 * @cacheMode cache
	 */
	public function getTicket($accessToken)
	{
		return $this->get_jsapi_ticket([
			'access_token' => $accessToken,
		])['ticket'];
	}
}

/**
 * 微信
 *
 * Class Weixin
 */
class Weixin
{
	/**
	 * 验证
	 *
	 * @param string $token
	 * @param array $params
	 * @throws Desire_Exception
	 */
	public static function check($token, $params = [])
	{
		Desire::validator($params, [
			'signature' => 'not_empty',
			'timestamp' => 'not_empty',
			'nonce' => 'not_empty',
		]);

		if ($params['signature'] != Desire_Math::hashArr($token, $params['timestamp'], $params['nonce'])) {
			throw new Desire_Exception('invalid signature');
		}

		if (isset($params['echostr'])) {
			echo $params['echostr'];
			exit;
		}
	}

	/**
	 * @param $data
	 * @param string $className
	 * @return mixed
	 * @throws Desire_Exception
	 */
	public static function analyzer($data, $className = 'Weixin_AnalyzerExample')
	{
		$analyzer = Desire::object($className, $data);
		if (!$analyzer instanceof Weixin_AnalyzerAbstract) {
			throw new Desire_Exception("Class `%s' was not instanceof Weixin_AnalyzerAbstract", $className);
		}
		return $analyzer->result();
	}
}