<?php

class Blaze_Api_Top extends TopClient
{
	protected static $instance = null;
	public $containerUrl = "http://container.open.taobao.com/container";

	/**
	 * 构造方法声明为private，防止直接创建对象
	 */
	private function __construct() {
		$this->appkey = Desire_Config::get('app.top.appkey');
		$this->secretKey = Desire_Config::get('app.top.appsecret');
		$this->gatewayUrl = Desire_Config::get('app.top.api_url');
		$this->containerUrl = Desire_Config::get('app.top.container_url');
		$this->loginUrl = Desire_Config::get('app.top.login_url');
	}

	/**
	 * @return Blaze_Api_Top
	 */
	public static function getInstance() {
		self::$instance || self::$instance = new self();
		return self::$instance;
	}

	/**
	 * 过滤url
	 * @param string $url
	 */
	protected function filterUrl($url) {
		return $url;
	}
	
	/**
	 * 登录URL
	 * 
	 * @param string $callbackUrl
	 * @param string $appkey
	 * @return string
	 */
	public function getLoginUrl($callbackUrl, $appkey = null) {
		is_null($appkey) && $appkey = $this->appkey;

		$authUrl = sprintf('%s?appkey=%s&back_url=%s',
			$this->containerUrl,
			$appkey,
			urlencode($callbackUrl)// 回调地址
		);
		
		return sprintf('%s?redirect_url=%s',
			$this->filterUrl('https://login.taobao.com/member/login.jhtml'),
			urlencode($authUrl)
		);
	}
	
	/**
	 * 付款URL
	 * 
	 * @param string $bizOrderId 淘宝订单号
	 * @return string
	 */
	public function getTradePayUrl($bizOrderId) {
		return sprintf('%s?biz_order_id=%s&biz_type=200',
			$this->filterUrl('http://trade.taobao.com/trade/pay.htm'),
			$bizOrderId
		);
	}
	
	/**
	 * 交易详情URL
	 * 
	 * @param string $bizOrderId 淘宝订单号
	 * @return string
	 */
	public function getTradeItemDetailUrl($bizOrderId) {
		return sprintf('%s?bizOrderId=%s&his=false',
			$this->filterUrl('http://trade.taobao.com/trade/detail/trade_item_detail.htm'),
			$bizOrderId
		);
	}
	
	/**
	 * 确认收货URL
	 * 
	 * @param string $bizOrderId 淘宝订单号
	 * @return string
	 */
	public function getTradeConfirmGoodsUrl($bizOrderId) {
		return sprintf('%s?biz_order_id=%s',
			$this->filterUrl('http://trade.taobao.com/trade/confirm_goods.htm'),
			$bizOrderId
		);
	}
	
	/**
	 * 查看物流URL
	 * 
	 * @param string $bizOrderId 淘宝订单号
	 * @return string
	 */
	public function getOrderLogisticsDetailUrl($bizOrderId) {
		return sprintf('%s?trade_id=%s',
			$this->filterUrl('http://wuliu.taobao.com/user/order_detail_new.htm'),
			$bizOrderId
		);
	}
	
	/**
	 * 评分URL
	 * 
	 * @param string $bizOrderId 淘宝订单号
	 * @return string
	 */
	public function getRemarkSellerUrl($bizOrderId) {
		return sprintf('%s?trade_id=%s',
			$this->filterUrl('http://rate.taobao.com/remark_seller.jhtml'),
			$bizOrderId
		);
	}
	
	/**
	 * 申请退款URL
	 * 
	 * @param string $bizOrderId 淘宝订单号
	 * @return string
	 */
	public function getFillRefundAgreementUrl($bizOrderId) {
		return sprintf('%s?bizOrderId=%s',
			$this->filterUrl('http://refund.taobao.com/fill_refundAgreement.htm'),
			$bizOrderId
		);
	}
	
	/**
	 * 订单快照URL
	 * 
	 * @param string $bizOrderId 淘宝订单号
	 * @return string
	 */
	public function getTradeSnapDetailUrl($bizOrderId) {
		return sprintf('%s?tradeID=%s',
			$this->filterUrl('http://trade.taobao.com/trade/detail/tradeSnap.htm'),
			$bizOrderId
		);
	}
	
	/**
	 * 投诉维权URL
	 * 
	 * @param string $bizOrderId 淘宝订单号
	 * @return string
	 */
	public function getRightsplatUrl($bizOrderId) {
		return sprintf('%s?trade_id=%s',
			$this->filterUrl('http://support.taobao.com/myservice/rightsplat/alert_rights.htm'),
			$bizOrderId
		);
	}
	
	/**
	 * 查看用户店铺URL
	 * 
	 * @param string $userNumberId 用户id
	 * @return string
	 */
	public function getViewShopUrl($userNumberId) {
		return sprintf('%s?user_number_id=%s',
			$this->filterUrl('http://store.taobao.com/shop/view_shop.htm'),
			$userNumberId
		);
	}
	
	/**
	 * 解析top_parameters
	 */
	public function parseParameters($topParameters) {
		$result = array();
		$topParameters = str_replace(' ', '+', urldecode($topParameters));
		parse_str(iconv('gbk', 'utf-8//TRANSLIT', base64_decode($topParameters)), $result);
		return $result;
	}
	
	/**
	 * 获取授权地址
	 */
	public function getVerifyUrl($back_url, $extra_param = array()) {
		$verify_url = $this->containerUrl . "?appkey=" . $this->appkey . "&back_url=" . urlencode($back_url);
		if (is_array($extra_param)) {
			$verify_url .= '&' . http_build_query($extra_param);
		}
		return $verify_url;
	}
	
	/**
	 * 验证回调签名函数
	 */
	public function verifyTopResponse($topParameters, $topSession, $topSign, $appKey = null, $appSrecet = null) {
		$appKey || $appKey = $this->appkey;
		$appSrecet || $appSrecet = $this->secretKey;
		$topParameters = str_replace(' ', '+', $topParameters);
		return $topSign == base64_encode(md5($appKey.$topParameters.$topSession.$appSrecet, true));
	}
	
	/**
	 * 获取淘宝的商品评价总数
	 */
	public function getTraderateNum($numIid) {
		$requestUrl = 'http://count.taobao.com/counter2';
		$requestUrl .= '?keys=ICE_3_feedcount-'.$numIid.'&t='.Desire_Time::now();
		$requestUrl .= '&callback=TShop.mods.SKU.Stat.setReviewCount';
		
		try {
			$resp = $this->curl($requestUrl);
		}
		catch (Exception $e) {
			$this->logCommunicationError('getTraderateNum', $requestUrl,"HTTP_ERROR_" . $e->getCode(), $e->getMessage());
			return 0;
		}

		if ($resp){
			preg_match('/:([0-9]+)\}/', $resp, $match);
			if (count($match) > 1){
				return intval($match[1]);
			}
		}
		return 0;
	}
	
	/**
	 * 根据淘宝信用等级返回等级图标
	 */
	public static function getLevelImg($level) {
		$leveimg='';
		if ($level <= 5) {
			$leveimg = 's_red_'.$level.'.gif';
		} else if($level <= 10) {
			$tmp = $level-5;
			$leveimg = 's_blue_'.$tmp.'.gif';
		} else if($level <= 15) {
			$tmp = $level-10;
			$leveimg = 's_cap_'.$tmp.'.gif';
		} else if($level <= 20) {
			$tmp = $level-15;
			$leveimg = 's_crown_'.$tmp.'.gif';
		}
		return 'http://pics.taobaocdn.com/newrank/' . $leveimg;
	}
	
	/**
	 * 获取买家交易记录
	 */
	public function getTradelogs($numIid, $bidPage = 1, $pageSize = 15, $userId = null) {
		$result = array();
		//获取 userId
		if (is_null($userId)) {
			$item = $this->taobao_item_get(array(
				'fields' => 'nick',
				'num_iid' => $numIid,
			));
			if (!isset($item['item']['nick'])) return $result;
			
			$item = $this->taobao_user_get(array(
				'fields' => 'user_id',
				'nick' => $item['item']['nick'],
			));
			if (!isset($item['user']['user_id'])) return $result;
			$userId = $item['user']['user_id'];
		}
		
		$requestUrl = 'http://tbskip.taobao.com/json/show_buyer_list.htm';
		$requestUrl .= sprintf('?ends=%s000&item_id=%s&seller_num_id=%s&bid_page=%d&page_size=%d&is_start=true',
			Desire_Time::now(),
			$numIid,
			$userId,
			$bidPage,
			$pageSize
		);
		
		try {
			$resp = $this->curl($requestUrl);
		}
		catch (Exception $e) {
			$this->logCommunicationError('getTradelogs', $requestUrl,"HTTP_ERROR_" . $e->getCode(), $e->getMessage());
			return $result;
		}
		
		if (!$resp) return $result;
		
		$resp = iconv('gbk', 'utf-8//TRANSLIT', $resp);
		
		//获取买家
		$buys_temp = strip_tags($resp, '<tr><br/><td><a><img>');
		$buys_temp = preg_replace('/<br\/>[^<]+/i', '', $buys_temp);
		
		$a = explode('<tr', $buys_temp);
		foreach ($a as $k => $v) {
			preg_match('/http:\/\/trade\..*taobao\.[^"]+/i', $v, $match1);
			if (empty($match1)) continue;
			
			$tradeLog = array();
			$tradeLog['url'] = trim($match1[0], '\\');
			
			preg_match_all('/<td[^>]*>(.*)<\/td>/iUs', $v, $match);
			if (isset($match[1]) && count($match[1]) != 6) continue;
			//匹配数据开始
			$tmp = explode('<img', $match[1][0]);
			
			if (preg_match('/taobao.com/i', $tmp[0])) {
				preg_match('/href="([^"]+)"[^>]*>(.*)<\/a.*href="([^"]+)"/iUs', $tmp[0], $m);
				$tradeLog['buyer_url'] = trim($m[1]);
				$tradeLog['buyer'] = trim($m[2]);
				$tradeLog['rate_url'] = trim($m[3]);
			} else {
				$tradeLog['buyer'] = trim($tmp[0]);
			}
			
			$imgs = array();
			for ($i = 1, $j = count($tmp); $i < $j; $i++) {
				$img = array();
				preg_match('/src="([^"]+)"/i', $tmp[$i], $m);
				isset($m[1]) && $img['src'] = $m[1];
				
				preg_match('/title="([^"]+)"/i', $tmp[$i], $m);
				isset($m[1]) && $img['title'] = $m[1];
				
				preg_match('/alt="([^"]+)"/i', $tmp[$i], $m);
				isset($m[1]) && $img['title'] = $m[1];
				//rate如果有链接
				if ($i == 1 && isset($tradeLog['rate_url'])) {
					$img['url'] = $tradeLog['rate_url'];
					unset($tradeLog['rate_url']);
				}
				
				$imgs[] = $img;
			}
			
			$tradeLog['imgs'] = $imgs;
			
			preg_match('/<a[^>]*>(.*)<\/a>(.*)$/iUs', $match[1][1], $tmp);
			isset($tmp[1]) && $tradeLog['title'] = trim($tmp[1]);
			
			$tradeLog['sku'] = isset($tmp[2]) ? trim($tmp[2]) : '默认款式';
			$tradeLog['price'] = trim($match[1][2]);
			$tradeLog['num'] = trim($match[1][3]);
			$tradeLog['time'] = trim($match[1][4]);
			$tradeLog['buy_status'] = trim($match[1][5]);
			
			$result['list'][]  = $tradeLog;
		}
		
		//获取分页信息
		$buys_page = strip_tags($resp, '<a><span>');
		$b = explode('page-info', $buys_page);
		if (count($b) > 1) {
			preg_match_all('/>([0-9\…]+)</', $b[1], $mat);
			if (count($mat) > 1){
				$result['paginator'] = $mat[1];
			}
		}
		
		return $result;
	}
	
	/**
	 * 抓取某个商品页面内容
	 * @param number $numIid
	 */
	public function getItemPage($numIid) {
		$requestUrl = 'http://item.taobao.com/item.htm';
		$requestUrl .= '?id='.$numIid.'&t='.Desire_Time::now();
		
		$resp = '';
		
		try {
			$resp = $this->curl($requestUrl);
		}
		catch (Exception $e) {
			$this->logCommunicationError('getItemPage', $requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
		}
		return iconv('gbk', 'utf-8//TRANSLIT', $resp);
	}
	
	/**
	 * 获取买家交易记录总数
	 */
	public function getTradelogNum($numIid) {
		$resp = $this->getItemPage($numIid);
		
		if ($resp) {
			preg_match('/id="deal-record".*<em>(\d+)<\/em>/iUs', $resp, $match);
			if (count($match) > 1) {
				return intval($match[1]);
			}
		}
		return 0;
	}
	
	/**
	 * 同时获取多个用户的VIP等级
	 *
	 * @param array $userIds
	 * @return array
	 */
	public function getUserVipLevels(array $userIds) {
		$result = array();
		foreach($userIds as $userId) {
			$result[] = $this->getUserVipLevel($userId);
		}
		return $result;
	}

	/**
	 * 获取单个用户的VIP等级
	 * 
	 * @param number $userId
	 */
	public function getUserVipLevel($userId) {
		$requestUrl = 'http://vip.taobao.com/service/queryVipLevel.do';
		$requestUrl .= '?userId='.$userId.'&src=qutao.com&t='.Desire_Time::now();

		try {
			$resp = $this->curl($requestUrl);
		}
		catch (Exception $e) {
			$this->logCommunicationError('getUserVipLevel', $requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
			return array();
		}

		if ($resp){
			//{"message":"SUCCESS","result":true,"level":3}
			//{"message":"USER_NOT_FOUND","result":false}
			//{"message":"IS_NOT_VIP_USER","result":false}
			$tmp = json_decode($resp, true);

			return isset($tmp['level']) ? array('vipLevel' => $tmp['level'], 'userId' => $userId) : array();
		}
		return array();
	}

	/**
	 * 根据淘宝用户vip等级返回等级图标
	 */
	public static function getVipLevelImg($level, $small = false) {
		$leveimg = '';
		switch ($level) {
			case -2: break;//VIP普通会员(无图标)
			case -1: break;//VIP普通会员(无图标)
			case 0://VIP荣誉会员(无图标) 
			case 1: $leveimg = 'v1'; break;//VIP黄金会员
			case 2: $leveimg = 'v2'; break;//VIP白金会员
			case 3: $leveimg = 'v3'; break;//VIP钻石会员
			case 4: $leveimg = 'v4'; break;//VIP至尊会员
			case 5: break;//vip5会员，暂时没有这类用户，名称也未定
			case 10: $leveimg = 'v10'; //淘宝达人
			default: //体验VIP会员在该接口中无法体现
		}
		
		if (empty($leveimg)) return false;
		
		$small && $leveimg .= 's';
		return 'http://a.tbcdn.cn/app/marketing/vip/vip2/icons/' . $leveimg . '.gif';
	}
}