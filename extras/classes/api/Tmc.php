<?php

require_once 'taobao_mc.php';

class TmcApi extends Taobao_MessageCenter
{
	public $messageCenterConfig = array(
		'service_url' => "http://mcgw.taobao.com:8047/MessageSenderWebService?wsdl",
		'source_id' => "taomanyi*taomanyi_notice",
		'template_id'   => "142134331",
		'message_type_notice' => "142510988",
		'message_type_emersion' => "142271949",
	);
	
	public function __construct() {
		parent::Taobao_MessageCenter($this->messageCenterConfig);
	}
	
	/**
	 * 获取单个用户的在线状态
	 * @param array $nick
	 * @return number
	 */
	public function getUserStatus($nick) {
		$status = $this->getUserStatuses(array($nick));
		return isset($status[$nick]) ? $status[$nick] : 0;
	}
	
	/**
	 * 获取多个用户的在线状态
	 * @param array $nicks
	 * @return array
	 */
	public function getUserStatuses(array $nicks) {
		$requestUrl = 'http://amos.alicdn.com/muliuserstatus.aw';
		$requestUrl .= '?beginnum=0&site=cntaobao&charset=utf-8&uids=' . implode(';', $nicks);
		$response = Desire_Http::get($requestUrl);
		//online[0]=1;online[1]=1;online[2]=0;
		if ($response->state !== 200) return array();
		$statuses = explode(';', $response->data);
		$userStatuses = array();
		for ($i = 0, $j = count($nicks); $i < $j; $i++) {
			$userStatuses[$nicks[$i]] = (int)substr($statuses[$i], -1);
		}
		return $userStatuses;
	}
	
	/**
	 * 获取淘宝旺旺弹出聊天url
	 * @param string $nick
	 */
	public function getUserTalkUrl($nick) {
		$requestUrl = 'http://www.taobao.com/webww/';
		$requestUrl .= '?ver=1&&touid=cntaobao'.urlencode($nick).'&siteid=cntaobao&status=2&charset=utf-8';
		//$requestUrl = 'http://amos.im.alisoft.com/msg.aw';
		//$requestUrl .= '?v=2&uid='.urlencode($nick).'&site=cntaobao&s=2&charset=utf-8';
		return $requestUrl;
	}
}