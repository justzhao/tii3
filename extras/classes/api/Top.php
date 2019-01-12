<?php

require_once 'TopClient.php';

class TopApi extends TopClient
{
	protected $taobaokePid;
	public $containerUrl = "http://container.open.taobao.com/container";
	
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
		$topParameters = str_replace(' ', '+', $topParameters);
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
	 * 获取卖家店铺的基本信息
	 * @param string $nick 卖家昵称
	 * @return array
	 */
	public function getShopInfo($nick) {
		$item = $this->taobao_shop_get(array(
			'fields' => 'sid,cid,nick,title,desc,bulletin,pic_path,created,modified,shop_score,remain_count,all_count,used_count',
			'nick' => $nick,
		));
		return isset($item['shop']) ? $item['shop'] : array();
	}
	
	/**
	 * 获取单个用户信息
	 * @param string $nick 昵称
	 * @return array
	 */
	public function getUserInfo($nick) {
		if (strpos($nick, '*') !== false) return array();
		
		$item = $this->taobao_user_get(array(
			'fields' => 'user_id,uid,nick,sex,buyer_credit,seller_credit,location,created,last_visit,birthday,type,has_more_pic,item_img_num,item_img_size,prop_img_num,prop_img_size,auto_repost,promoted_type,status,alipay_bind,consumer_protection,alipay_account,alipay_no,real_name,avatar,liangpin,sign_food_seller_promise,has_shop,is_lightning_consignment,vip_info,sign_consumer_protection,phone,mobile,email,magazine_subscribe,vertical_market,manage_book,online_gaming',
			'nick' => $nick,
		));
		return isset($item['user']) ? $item['user'] : array();
	}
	
	/**
	 * 获取单个用户信息
	 * @param string $nick 昵称
	 * @return array
	 */
	public function getUserInfos(array $nicks) {
		$filtedNicks = array();
		foreach ($nicks as $nick) {
			if (strpos($nick, '*') !== false) continue;
			$filtedNicks[] = $nick;
		}
		if (empty($filtedNicks)) return array();
		
		$items = $this->taobao_users_get(array(
			'fields' => 'user_id,uid,nick,sex,buyer_credit,seller_credit,location,created,last_visit,birthday,type,has_more_pic,item_img_num,item_img_size,prop_img_num,prop_img_size,auto_repost,promoted_type,status,alipay_bind,consumer_protection,alipay_account,alipay_no,real_name,avatar,liangpin,sign_food_seller_promise,has_shop,is_lightning_consignment,vip_info,sign_consumer_protection,phone,mobile,email,magazine_subscribe,vertical_market,manage_book,online_gaming',
			'nicks' => implode(',', $filtedNicks),
		));
		
		return isset($items['users']) ? $items : array();
	}
	
	/**
	 * 根据查询条件查询淘宝的商品
	 * 
	 * @param array $params taobao.items.search 参数
	 * @return array
	 */
	public function searchItems(array $params) {
		if (!array_key_exists('q', $params)) return array();
		
		$params = array_merge(
			$params,
			array(
				'fields' => 'detail_url,num_iid,title,nick,type,desc,skus,props_name,created,is_lightning_consignment,is_fenxiao,template_id,cid,seller_cids,props,input_pids,input_str,pic_url,num,valid_thru,list_time,delist_time,stuff_status,location,price,post_fee,express_fee,ems_fee,has_discount,freight_payer,has_invoice,has_warranty,has_showcase,modified,increment,approve_status,postage_id,product_id,auction_point,property_alias,item_imgs,prop_imgs,outer_id,is_virtual,is_taobao,is_ex,is_timing,videos,is_3D,score,volume,one_station,second_kill,auto_fill,violation,is_prepay,ww_status,wap_desc,wap_detail_url,after_sale_id,cod_postage_id,sell_promise',
			)
		);
		
		$items = $this->taobao_items_search($params);
		isset($items['item_search']['item_categories']['item_category']) && $items['taobao_category'] = $this->buildCategory($items['item_search']['item_categories']['item_category']);
		
		return $items['total_results'] ? $items : array();
	}
	
	/**
	 * 搜索结果相关分类
	 */
	private function buildCategory($taobao_category) {
		$query_category = array();

		if (isset($taobao_category['category_id'])) {
			$query_category[] = $taobao_category['category_id'];
		} else {
			foreach ($taobao_category as $value) {
				$query_category[] = $value['category_id'];
			}
		}

		$query_category = implode(',', $query_category);
		$category_result = $this->getTaoBaoCategory($query_category);

		if (isset($taobao_category['category_id'])) {
			return array(
				0 => array(
					'category_id' => $taobao_category['category_id'],
					'category_name' => $category_result[$taobao_category['category_id']],
					'count'  => $taobao_category['count']
				)
			);
		}

		$result = array();
		foreach ($taobao_category as $value) {
			if (!isset($category_result[$value['category_id']])) continue;
			$result[] = array(
				'category_id' => $value['category_id'],
				'category_name' => $category_result[$value['category_id']],
				'count' => $value['count']
			);
		}

		return $result;
	}
	
	/**
	 * 获取淘宝分类
	 */
	public function getTaoBaoCategory($categoryId) {
		$taobao_category_result = $this->taobao_itemcats_get(array(
			'fields' => 'cid,name',
			'cids' => $categoryId,
		));
		
		$item_cat = $taobao_category_result['item_cats']['item_cat'];
		
		if (isset($item_cat['cid'])) {
			return array(
				$item_cat['cid'] => $item_cat['name']
			);
		}
		
		$result = array();
		foreach ($item_cat as $value) {
			$result[$value['cid']] = $value['name'];
		}
		
		return array_unique($result);
	}

	/**
	 * 得到单个商品信息
	 * @param number $numIid 商品数字ID 
	 * @return array
	 */
	public function getItemInfo($numIid) {
		$item = $this->taobao_item_get(array(
			'fields' => 'detail_url,num_iid,title,nick,type,desc,skus,props_name,created,promoted_service,is_lightning_consignment,is_fenxiao,template_id,cid,seller_cids,props,input_pids,input_str,pic_url,num,valid_thru,list_time,delist_time,stuff_status,location,price,post_fee,express_fee,ems_fee,has_discount,freight_payer,has_invoice,has_warranty,has_showcase,modified,increment,approve_status,postage_id,product_id,auction_point,property_alias,item_imgs,prop_imgs,outer_id,is_virtual,is_taobao,is_ex,is_timing,videos,is_3D,score,volume,one_station,second_kill,auto_fill,violation,is_prepay,ww_status,wap_desc,wap_detail_url,after_sale_id,cod_postage_id,sell_promise',
			'num_iid' => $numIid,
		));
		return isset($item['item']) ? $item['item'] : array();
	}

	/**
	 * 运费模板数据结构转换
	 * 将 taobao_delivery_template_get 获取的数据使与老API taobao_postage_get 的结构一致

	 * @param string $nick 昵称
	 * @param int $postageId 新邮费模板id
	 * @return array
	 */
	public function getPostage($nick, $postageId) {
		$template = $this->taobao_delivery_template_get(array(
			'fields'      => 'template_id,template_name,created,modified,supports,assumer,valuation,fee_list,query_express,query_ems,query_cod,query_post',
			'template_ids'   => $postageId,
			'user_nick'      => $nick
		));

		if (!isset($template['total_results']) || !$template['total_results']) return array();
		$template = $template['delivery_templates']['delivery_template'][0];


		$postage = array(
			'created' => $template['created'],
			'modified' => $template['modified'],
			'name' => $template['name'],
			'postage_id' => $template['template_id'],
		);


		foreach ($template['fee_list']['top_fee'] as $fee) {
			if ($fee['destination'] == 1 && in_array($fee['service_type'], array('post', 'ems', 'express'))) {
				$postage[$fee['service_type'].'_increase'] = $fee['add_fee'];
				$postage[$fee['service_type'].'_price'] = $fee['start_fee'];
				continue;
			}

			# 保持结构不被破坏、兼容现有代码
			$postage['postage_modes']['postage_mode'][] = array(
				'dests'   => $fee['destination'],
				'increase'   => $fee['add_fee'],
				'price'   => $fee['start_fee'],
				'type'   => $fee['service_type'],
			);
		}
		return $postage;
	}
	
	/**
	 * 获取单个商品的所有 SKU
	 * @param number $numIid 商品的数字IID
	 * @return array
	 */
	public function getItemSkus($numIid) {
		$item = $this->taobao_item_skus_get(array(
			'fields' => 'sku_id,num_iid,properties,quantity,price,outer_id,created,modified,status,extra_id,memo',
			'num_iids' => $numIid,
		));
		
		return isset($item['skus']) ? $item : array();
	}
	
	/**
	 * 获取淘宝的商品评价列表
	 * @param $pageSize 每页显示的条数，允许值：5、10、20、40
	*/
	public function getTraderates($numIid, $page = 1, $pageSize = 20) {
		// 获取单个商品的详细信息
		$item = $this->taobao_item_get(array(
			'fields' => 'nick',
			'num_iid' => $numIid,
		));
		if (!isset($item['item']['nick'])) return null;
		
		// 通过商品id查询对应的评价信息
		$items = $this->taobao_traderates_search(array(
			'num_iid' => $numIid,
			'seller_nick' => $item['item']['nick'],
			'page_no' => $page,
			'page_size' => $pageSize,
		));
		return $items['total_results'] ? $items : array();
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
			isset($tmp[2]) && $tradeLog['sku'] = trim($tmp[2]);
			
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
	
	/**
	 * 获取限时折扣信息
	 * @param number $numIid
	 * @param string $nick
	 */
	public function getLimitPromotionRate($numIid, $nick = null) {
		//默认无折扣
		$limitPromotionRate = array('type' => 'none');
		
		if (is_null($nick)) {
			$item = $this->taobao_item_get(array(
				'fields' => 'nick',
				'num_iid' => $numIid,
			));
			if (!isset($item['item']['nick'])) return $limitPromotionRate;
			$nick = $item['item']['nick'];
		}
		
		$item = $this->taobao_user_get(array(
			'fields' => 'user_id',
			'nick' => $nick,
		));
		if (!isset($item['user']['user_id'])) return $limitPromotionRate;
		
		$limitPromotionRate['nick'] = $nick;
		$limitPromotionRate['user_id'] = $item['user']['user_id'];
		
		$requestUrl = 'http://tbskip.taobao.com/limit_promotion_item.htm';
		$requestUrl .= '?auctionId='.$numIid.'&userId='.$item['user']['user_id'].'&t='.Desire_Time::now();
		
		try {
			$resp = $this->curl($requestUrl);
		}
		catch (Exception $e) {
			$this->logCommunicationError('getLimitPromotionRate', $requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
			return $limitPromotionRate;
		}
		
		if (empty($resp)) return $limitPromotionRate;
		
		//TB.LimitPromotion_8762509426_512671209 = '10小时3分钟;5.55;68.82';
		preg_match("/TB\.LimitPromotion.*'([^']+)'/is", $resp, $match);
		if (!isset($match[1])) return $limitPromotionRate;
		if (strpos($match[1], ';') === false) return $limitPromotionRate;
		list($time, $rate, $price) = explode(';', $match[1]);
		$time = str_replace(array(
				'小时', '分钟', '星期', '天', '年', '月'
			), array(
				' hours ', ' seconds ', ' weeks ', ' days ', ' years ', ' months '
			),
			iconv('gbk', 'utf-8//TRANSLIT', $time)
		);
		
		$limitPromotionRate['time'] = Desire_Time::totime($time);
		$limitPromotionRate['rate'] = $rate;
		$limitPromotionRate['price'] = $price;
		
		//获取限时折扣类型
		$pageResp = $this->getItemPage($numIid);
		if (empty($pageResp)) return $limitPromotionRate;
		
		if (preg_match('/id="J_EmLimitPromCountdown"/i', $pageResp)) {
			$limitPromotionRate['type'] = 'progress';
		} else if (preg_match('/valLimitPromInfo2:.*\{.*\}/is', $pageResp)) {
			$limitPromotionRate['type'] = 'timeLeft';
		}
		
		if ($limitPromotionRate['type'] == 'none') return $limitPromotionRate; //没找到限时折扣类型
		
		if (preg_match('/id="J_SpanPromLimitCount"[^>]*>(\d+)</i', $pageResp, $pageMatch)) {
			$limitPromotionRate['limitCount'] = $pageMatch[1];
		}
		
		return $limitPromotionRate;
	}
	
	/**
	 * 获取淘宝VIP会员的打折幅度
	 * @param number $numIid
	 * @return array array("VIP 金　卡"， "VIP 白金卡"， "VIP 钻石卡")
	 */
	public function getVipDiscountRegion($numIid) {
		$vipDiscountRegion = array();
		
		$pageResp = $this->getItemPage($numIid);
		if (empty($pageResp)) return $vipDiscountRegion;
		
		if (!preg_match('/id="J_VipPriceData"/i', $pageResp)) {
			return $vipDiscountRegion;
		}
		
		if (preg_match('/vipDiscountRegion: (\[[^\]]+\])/i', $pageResp, $pageMatch)) {
			if (!isset($pageMatch[1])) return $vipDiscountRegion;
			$vipDiscountRegion = json_decode($pageMatch[1], true);
		}
		
		return $vipDiscountRegion;
	}
	
	/**
	 * 物流流转信息查询
	 * @param number $tid 淘宝交易号
	 * @param string $sellerNick 卖家昵称 
	 * @return array
	 */
	public function getLogisticsTrace($tid, $sellerNick) {
		$logisticsTrace = $this->taobao_logistics_trace_search(array(
			'tid' => $tid,
			'seller_nick' => $sellerNick,
		));
		
		return isset($logisticsTrace['tid']) ? $logisticsTrace : array();
	}
	
	/**
	 * 获取某件商品的淘宝客的链接地址
	*/
	public function getTaobaokeClickUrl($numIid, $outerCode = null) {
		$taobaoke = $this->getTaobaoke($numIid, $outerCode);
		if (isset($taobaoke['taobaoke_items']['taobaoke_item']['0']['click_url'])) {
			return $taobaoke['taobaoke_items']['taobaoke_item']['0']['click_url'];
		}
		
		return $this->filterUrl('http://item.taobao.com/item.htm?id='.$numIid);
	}
	
	/**
	 * 获取多件商品的淘宝客信息
	 * @param array $numIids 淘宝客商品数字id串
	*/
	public function getTaobaokes(array $numIids, $outerCode = null) {
		$fields = array(
				'fields' => 'commission_rate,iid,num_iid,title,nick,pic_url,price,click_url,commission,commission_num,commission_volume,shop_click_url,seller_credit_score,item_location,volume,taobaoke_cat_click_url,keyword_click_url',
				'pid' => $this->taobaokePid,
				'num_iids' => implode(',', $numIids),
				
		);
		$outerCode && $fields['outer_code'] = $outerCode;
		
		$items = $this->taobao_taobaoke_items_convert($fields);
		
		return isset($items['taobaoke_items']) ? $items : array();
	}
	
	/**
	 * 获取某件商品的淘宝客信息
	 * @param string $numIid 淘宝客商品数字id串
	*/
	public function getTaobaoke($numIid, $outerCode = null) {
		return $this->getTaobaokes(array($numIid), $outerCode);
	}
	
	/**
	 * 根据查询条件查询淘宝客的商品
	 * 
	 * @param string $nick
	 * @param array $params taobao.taobaoke.items.get 参数
	 * @return array
	 */
	public function getTaobaokeItems(array $params, $outerCode = null) {
		if (!array_key_exists('keyword', $params) && !array_key_exists('cid', $params)) return array();
		$fields = array(
				'fields' => 'num_iid,title,nick,pic_url,price,click_url,commission,commission_rate,commission_num,commission_volume,shop_click_url,seller_credit_score,item_location,volume',
				'pid' => $this->taobaokePid,
		);
		$outerCode && $fields['outer_code'] = $outerCode;
		
		$params = array_merge($params, $fields);
		
		$items = $this->taobao_taobaoke_items_get($params);
		
		return $items['total_results'] ? $items : array();
	}
	
	/**
	 * Sku名字对应关系
	 * @param string $propsName
	 */
	public function getSkuPropsNameMap($propsName) {
		if (empty($propsName)) return array();
		$propsNames = explode(';', $propsName);
		$propsNameArr = array();
		foreach($propsNames as $k => $v) {
			$tmp = explode(':', $v);
			$propsNameArr[$tmp[0]] = $tmp[2];
			$propsNameArr[$tmp[1]] = $tmp[3];
		}
		return $propsNameArr;
	}
	
	/**
	 * VIP加密接口
	 * @param string $content 需要加密的明文 
	 * @return boolean|array = array('success', 'message');
	 * message: INPUT_ERROR: 参数错误, SYSTEM_ERROR: 系统错误,ENCRYPT_ERROR:加密错误 
	 */
	public function vipCooperEncrypt($content) {
		$requestUrl = $this->filterUrl('http://vip.taobao.com/cooper/coupon/encrypt.do');
		
		$postFields = array();
		$postFields['content'] = $content;
		$postFields['result'] = '';//返回的json对象result={json对象}，

		try {
			$resp = $this->curl($requestUrl, $postFields);
		}
		catch (Exception $e) {
			$this->logCommunicationError('vipCooperEncrypt', $requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
			return false;
		}
		
		return json_decode($resp, true);
	}
	
	
	/**
	 * $code: 任务编码 
	 * $userId: 用户数字ID（可选） 
	 * $nick: 用户nick（可选） 
	 * $accountNo: 支付宝No（可选,必须带0156结尾的） 
	 * @return boolean|array = array('success', 'message');
	 * 
	 * 注: userId,nick,accountNo必须包含一个 
	 * message: OVER_TIME: time超时（5分钟）, SIGN_ERROR: sign加密串错误或者参数不全 , SYSTEM_ERROR: 系统异常, COMPLETE: 任务完成成功, USER_NOT_FOUND: 找不到用户信息  
	 */
	public function vipCooperCompleteTask($code, $userId = null, $nick = null, $accountNo = null) {
		if (is_null($userId) && is_null($nick) && is_null($accountNo)) return false;
		
		//eg: code=88888888|nick=1275981083|time=System.currentTimeMillis() / 1000 
		$task = array();
		is_null($userId) || $task[] = sprintf('userId=%s', $userId);
		is_null($nick) || $task[] = sprintf('nick=%s', $nick);
		is_null($accountNo) || $task[] = sprintf('accountNo=%s', $accountNo);
		$task[] = sprintf('code=%s', $code);
		$task[] = sprintf('time=%s', Desire_Time::now());
		
		//do complete task
		$requestUrl = $this->filterUrl('http://vip.taobao.com/cooper/complete_task.do');
		
		$encrypt = $this->vipCooperEncrypt(implode('|', $task));
		
		if (empty($encrypt) || !isset($encrypt['message'])) return false;
		$postFields = array();
		$postFields['sign'] = $encrypt['message'];
		
		try {
			$resp = $this->curl($requestUrl, $postFields);
		}
		catch (Exception $e) {
			$this->logCommunicationError('vipCooperCompleteTask', $requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
			return false;
		}
		
		return json_decode($resp, true);
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