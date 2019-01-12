<?php
/**
 * 淘满意所有的接口入口
 * @author Alacner
 * @version $Id: Taomanyi.php 736 2012-07-20 00:35:03Z alacner $
 */
class TaomanyiApi
{
	protected $apiUrl = 'http://manyi.taobao.com/open_api.php';
	protected $appKey;
	protected $appSecret;
	protected $appSid;
	protected $resFormat = 'json';
	protected $apiParamArr = array();
	
	public function __construct() {
		$this->apiParamArr = array(
			'api_key' => $this->appKey,
			'format' => $this->resFormat,
			'timestamp' => Desire_Time::format('Y-m-d H:i:s')
		);
	}
	
	/**
	 * @desc生成签名
	 *
	 * @param $paramArr：api参数数组
	 * @return $sign
	 */
	public function createSign($paramArr, $apiParamArr = null) {
		is_null($apiParamArr) && $apiParamArr = $this->apiParamArr;
		$paramArr = array_merge($paramArr, $apiParamArr);
		ksort($paramArr);
		reset($paramArr);
		$sign = "";
		foreach ($paramArr as $key => $val) {
			if ($key !='' && $val !='') {
				$sign .= $key.'='.$val.'&';
			}
		}
		$sign = md5($sign . $this->appSecret);
		return $sign;
	}
	

	/**
	 * 生成字符串参数
	 * @param $paramArr：api参数数组
	 * @return $strParam
	 */
	public function createStrParam($paramArr, $apiParamArr = null) {
		is_null($apiParamArr) && $apiParamArr = $this->apiParamArr;
		$strParam = '';
		$paramArr = array_merge($paramArr, $apiParamArr);
		foreach ($paramArr as $key => $val) {
			if ($key != '' && $val !='') {
				$strParam .= $key.'='.urlencode($val).'&';
			}
		}
		return $strParam;
	}
	
	
	/**
	 * 创建访问的淘满意的地址
	 * @param $paramArr：api参数数组
	 */
	public function buildUrl($paramArr) {
		$sign = $this->createSign($paramArr);
		$strParam = $this->createStrParam($paramArr);
		$strParam .= 'sign='.$sign;
		return $this->apiUrl.'?'.$strParam;
	}
	
	
	/**
	 * 解析xml
	 */
	private function getXmlData ($strXml) {
		if (strpos($strXml, 'xml') === false) return array(); 
		
		$xmlCode = simplexml_load_string($strXml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$arrayCode = $this->get_object_vars_final($xmlCode);
		return $arrayCode ;
	}
	
	
	private function get_object_vars_final($obj) {
		is_object($obj) && $obj = get_object_vars($obj);
		if (is_array($obj)) {
			foreach ($obj as $key => $value) {
				$obj[$key] = $this->get_object_vars_final($value);
			}
		}
		return $obj;
	}
	
	
	public function returnResult($result) {
		switch ($this->resFormat) {
			case 'xml' :
				return $this->getXmlData($result);
			case 'json' :
				return json_decode($result, true);
			default:
				return array();
		}
	}
	
	
	/**
	 * 以GET方式访问api服务
	 * @param $paramArr：api参数数组
	 * @return $result
	 */
	public function getResult($method, $paramArr) {
		$paramArr['method'] = $method;
		$url = $this->buildUrl($paramArr);
		$result = Desire_Http::get($url);
		if ($result->state !== 200) $result->data = '';
		return $this->returnResult($result->data);
	}
	

	/**
	 * 以POST方式访问api服务
	 * @param $paramArr：api参数数组
	 * @return $result
	 */
	public function postResult($method, $paramArr = array(), $imageArr = array()) {
		$sign = $this->createSign($paramArr);
		$paramArr['sign'] = $sign;
		$paramArr['method'] = $method;
		$paramArr = array_merge($paramArr, $this->apiParamArr);

		$result = Desire_Http::post($this->apiUrl, array($paramArr, $imageArr));
		if ($result->state !== 200) $result->data = '';
		return $this->returnResult($result->data);
	}
	
	/**
	 * 查找站点返利比例
	 * @param number $siteId 站点Id
	 * @return array
	 */
	public function getFanliRateBySiteId($siteId) {
		$paramArr = array();
		$paramArr['site_id'] = $siteId;
		return $this->getResult('manyi.site.get_fanli_rate', $paramArr);
	}
	
	/**
	 * 获取系统返利抽佣比例
	 * @return array
	 */
	public function getSystemFanliRate() {
		$fanliRate = 0;
		$fanli = $this->getResult('manyi.account.system_rate', array());
		if (is_array($fanli) && array_key_exists('system_rate', $fanli)) {
			$fanliRate = floatval($fanli['system_rate']);
		}
		
		return $fanliRate;
	}
	
	
	/**
	 * 查找符合条件的商品
	 * @param array $paramArr
	 * @param mixed $fields
	 * @return array
	 */
	public function findItems($paramArr, $fields = '*,item_descip,detail_url,props,props_name,certify_description,base_group,per_number') {
		isset($paramArr['fields']) || $paramArr['fields'] = is_array($fields) ? implode(',', $fields) : $fields;
		return $this->getResult('manyi.item.search', $paramArr);
	}
	
	/**
	 * 利用查询方式获得某个商品id的信息
	 * @param int $itemId
	 */
	public function getItemByIdWithFind($itemId) {
		return $this->findItems(array('siid' => intval($itemId)));
	}
		
	
	/**
	 * 获取单个商品运费模板信息
	 * @param array $itemIds
	 * @param mixed $fields
	 * @return array
	 */
	public function getItemPostageById($postageId, $fields = '*') {
		$sku = $this->getItemPostageByIds($postageId, $fields);
		return isset($sku['items'][0][0]) ? $sku['items'][0][0] : array();
	}
	
	/**
	 * 获取商品运费模板信息
	 * @param array $itemIds
	 * @param mixed $fields
	 * @return array
	 */
	public function getItemPostageByIds($postageIds, $fields = '*') {
		$paramArr = array();
		$paramArr['postage_ids'] = is_array($postageIds) ? implode(',', $postageIds) : $postageIds;
		isset($paramArr['fields']) || $paramArr['fields'] = is_array($fields) ? implode(',', $fields) : $fields;
		return $this->getResult('manyi.item.postage_list', $paramArr);
	}
	
	
	/**
	 * 获取单个商品sku信息
	 * @param array $itemIds
	 * @param mixed $fields
	 * @return array
	 */
	public function getItemSkuByItemId($itemId, $fields = '*') {
		$sku = $this->getItemSkuByItemIds($itemId, $fields);
		return isset($sku['items'][0]) ? $sku['items'][0] : array();
	}
	
	/**
	 * 获取商品sku信息
	 * @param array $itemIds
	 * @param mixed $fields
	 * @return array
	 */
	public function getItemSkuByItemIds($itemIds, $fields = '*') {
		$paramArr = array();
		$paramArr['shop_item_ids'] = is_array($itemIds) ? implode(',', $itemIds) : $itemIds;
		isset($paramArr['fields']) || $paramArr['fields'] = is_array($fields) ? implode(',', $fields) : $fields;
		return $this->getResult('manyi.item.sku_list', $paramArr);
	}
	
	
	/**
	 * 获取某个商品的详细信息
	 * @param int $itemId
	 */
	public function getItemStatus($itemId) {
		$item = $this->getItemById($itemId);
		if (isset($item['status'])) return intval($item['status']);
		return 0;
	}
	
	/**
	 * 通过ID获取单个商品信息
	 * @param int $itemId
	 */
	public function getItemById($itemId) {
		$item = $this->getItemsByIds($itemId);
		if (isset($item[0])) return $item[0];
		return array();
	}
	
	/**
	 * 通过IDs获取商品信息
	 */
	public function getItemsByIds($itemIds) {
		$paramArr = array();
		$paramArr['item_ids'] = is_array($itemIds) ? implode(',', $itemIds) : $itemIds;
		return $this->getResult('manyi.item.get_items_by_id', $paramArr);
	}
	
	/**
	 * 获取多个商品的活动价格
	 * @param $numIids 
	 */
	public function getTaobaoPromotions(array $numIids, $taobaoNick = null) {
		$paramArr = array();
		$paramArr['item_id'] = implode(',', $numIids);
		is_null($taobaoNick) || $paramArr['taobao_nick'] = $taobaoNick;
		
		return $this->getResult('manyi.item.taobao_promotion', $paramArr);
	}
	
	/**
	 * 获取单个商品的活动价格
	 * @param $numIid 
	 */
	public function getTaobaoPromotion($numIid, $taobaoNick = null) {
		$taobaoPromotions = $this->getTaobaoPromotions(array($numIid), $taobaoNick);
		return isset($taobaoPromotions[$numIid]) ? $taobaoPromotions[$numIid] : array();
	}
	
	/**
	 * 获取订单列表
	 * @param array $paramArr
	 * @return array
	 */
	public function getOrderList($paramArr, $fields = '*') {
		isset($paramArr['fields']) || $paramArr['fields'] = is_array($fields) ? implode(',', $fields) : $fields;
		return $this->getResult('manyi.order.list', $paramArr);
	}
	
	
	/**
	 * 获取单个订单信息
	 * @param array $tradeNo
	 * @return array
	 */
	public function getOrderInfoByTradeNo($tradeNo, $fields = '*') {
		$paramArr = array();
		$paramArr['trade_no'] = is_array($tradeNo) ? implode(',', $tradeNo) : $tradeNo;
		$order = $this->getOrderList($paramArr, $fields);
		return (isset($order['total']) && $order['total']) ? $order['order'][0] : array();
	}
	
	
	/**
	 * 获取淘满意商品类目
	 */
	public function getCateList() {
		$cateList = $this->getResult('manyi.itemcat.list', array('fields' => 'cate_id,cate_name'));
		return isset($cateList['total_results']) ? $cateList['itemcat'] : array();
	}
	
	
	/**
	 * 获取商品同步销售地址
	 * @param mixed $itemId
	 */
	public function getPromotions($itemId) {
		is_array($itemId) && $itemId = implode(',', $itemId);
		return $this->getResult('manyi.item.promotion_list', array('shop_item_ids' => $itemId));
	}
	
	/**
	 * 获取单个商家信息
	 * @param int $memberId
	 */
	public function getSeller($memberId) {
		return $this->getResult('manyi.seller.get',array('member_id' => $memberId));
	}
	
	
	/**
	 * 获取多个商家信息
	 * @param int $memberId
	 */
	public function getSellers($memberId, $fields = '*') {
		$paramArr = array();
		$paramArr['member_id'] = is_array($memberId) ? implode(',', $memberId) : $memberId;
		$paramArr['fields'] = is_array($fields) ? implode(',', $fields) : $fields;
		return $this->getResult('manyi.seller.sellerinfos', $paramArr);
	}
	
	/**
	 * 创建淘满意平台用户接口
	 * @param array $params array(
	 * 		'sid' => 123, #站点ID，必须
	 * 		'uid' => 2, #所属平台的唯一识别ID，必须
	 * 		'user_name' => eppler #用户昵称, 必须
	 * 		'icon' => 'http://www.xx.com/a.jpg' #用户头像地址，使用绝对地址 ， 可选
	 * 		'gender' => '1', #用户性别，1表示男性，0表示女性， 可选
	 * 		'real_name' => '崔玉松', #真是姓名, 可选
	 * 		'email' => 'cuimuxi@sina.com', #邮件地址, 可选
	 * 		'alipay_account' => 'cuimuxi@163.com' #支付宝账户，可选
	 * 		'taobao_nick' => 'cuimuxi', #淘宝用户名, 可选
	 * 		'mobile' => '13688888888', #手机号码，可选	
	 * );
	 */
	public function createUser($params) {
		if (empty($params)) return false;
		return $this->getResult('manyi.user.create', array('data' => json_encode($params)));
	}
	
	/**
	 * 获取某个用户的详细信息
	 * @param string $userId 用户在淘满意的ID
	 */
	public function getUser($userId, $fields = '*') {
		$user = $this->getUsers(array($userId), $fields);
		return isset($user['users'][0]) ? $user['users'][0] : array();
	}
	
	/**
	 * 获取多个用户的详细信息
	 * @param string $userId 用户在淘满意的ID
	 */
	public function getUsers(array $userIds, $fields = '*') {
		$paramArr = array();
		$paramArr['pw_uid'] = implode(',', $userIds);
		$paramArr['fields'] = is_array($fields) ? implode(',', $fields) : $fields;
		return $this->getResult('manyi.user.userinfos', $paramArr);
	}
	
	/**
	 * 创建淘宝订单
	 * 
	 * @param array $param array(
	 * # 当使用系统已经有的地址时不需要传入地址详情，只需要一个有address_id key 的关联数组
	 * 'address' => array(
	 * #'address_id' => 123
	 * 'sid' => 123,
	 * 'real_name' => '崔玉松',
	 * 'province' =>  '浙江省',
	 * 'city' => '杭州市',
	 * 'area' => '西湖区',
	 * 'detail_address' => '文二路391号西湖国际科技大厦D楼5楼',
	 * 'zip_code' => '310099',
	 * 'mobile' => '13777777777',
	 * #以下为固定电话号码，和手机号码任选其一
	 * 'phone_area_code' => '0571',
	 * 'phone_num' => '88347412',
	 * 'phone_ext' => '38462',
	 * 'country' => '中国', #可选字段
	 * ),
	 *
	 * 'user' => array(
	 * 'pw_uid' => 1111,
	 * 'uid' => '123',
	 * 'taobao_nick' => '轻纱gg',
	 * 'user_name' => 'qutao_admin', 可选
	 * ),
	 *
	 * 'order' => array(
	 * 'item_id' => 23344,
	 * 'buy_num' => 3, 购买数量
	 * 'discount' => 1.5, 优惠额度
	 * #快递类型express | ems | selfpick
	 * 'post_type' => 'express', 可选
	 * 'post_fee' => '10.00', 可选
	 * 'return_url' => 'http://www.xxx.com'#支付宝订单必填参数
	 * 'alipay_config' => array(), #支付宝订单必填参数
	 * 'pay_info' => array(), #支付宝订单必填
 	 * 'sku_id' => '4930106562', #根据购买商品的条件决定是否传递本参数,
	 * 'is_anonymous' => 0, 是否是匿名购买,可选参数，如果是匿名值为1 ， 可选
	 * 'spid' => 12333, #专题ID， 可选参数
	 * 'buyer_msg' => '店主：给我发个红色的', #订单留言，  可选参数
	 * 'agent' => 'taomanyi', #订单来源, 可选，默认taomanyi
	 * 'alipay_seller_account' => 'cuimuxi@163.com', #卖家支付宝账号，支付宝订单必填
	 * 'pay_from_fanli_account' => '0.11' #利用余额支付的金额
	 * ),
	 * );
	 */
	public function createOrder($params) {
		if (empty($params)) return false;
		if (empty($params['order']['post_type'])) $params['order']['post_type'] = 'express';
		if (isset($params['order']['pay_from_fanli_account'])) {
			return $this->getResult('manyi.order.inner', array('data' => json_encode($params)));
		}
		return $this->getResult('manyi.order.create', array('data' => json_encode($params)));
	}
	
	
	/**
	 *  创建返利订单
	 *  
	 * @param array $param array(
            'user'=> array(
                'pw_uid' => 628, # 用户表中的UID
                #'sid' => 123, #站点ID 
                #'uid' => 0, #用户在调用者系统中的UID
                'taobao_nick' =>' 轻纱gg' #淘宝昵称
                ),
            'address' => array(
                #当使用系统已经有的地址时不需要传入地址详情，只需要一个有address_id key 的关联数组
                'address_id' => 1128 #平台的地址ID 
                'real_name' => '崔玉松' ,#用户真实姓名（收货姓名）
                'province' =>' 浙江省',
                'city' =>' 杭州市',
                'area' =>' 西湖区',
                'detail_address' => '文二路西湖国际D座5楼',
                'zip_code' => '310099',
                'mobile' => 13615756524,
                #以下为固定电话号码，和手机号码任选其一
                'phone_area_code'=> '0571',#电话区号
                'phone_num'=> '88374412',#固定电话号码 
                'phone_ext'=> '38462',#分机号 
                 ),
            'order' => array(
                'price' => 0.01, #商品单价 
                'buy_num' => 1, #购买数量
                'seller_taobao_nick' => 'flora12321', #卖家淘宝账号 
                'post_type' => 'express', #物流方式
                'pay_subject' => '风林lower002（运费模板）', #商品标题 
                'post_fee' => '10', #物流费用 
                'taobao_num_id'=> '10747699852', #商品的淘宝数字ID
                'freight_payer' => 'buyer', #物流费用承担方式 
                'item_pic_url' => 'http://img04.taobaocdn.com/bao/uploaded/i4/T1KpVVXjxJXXXObl3W_024513.jpg' #商品的缩略图首图 
                'commission' => '0.001', #返利佣金金额 
                'commission_rate' => '0.1', #佣金比率
                'agent' => 'taomanyi', #成交平台的来源标示
                'taobao_sku_id' => '10736636906', #淘宝商品的SKU ID
                'sku_properties_name' => 'a:2:{i:0;a:2:{s:1:"p";s:12:"颜色分类";s:1:"v";s:9:"紫罗兰";}i:1;a:2:{s:1:"p";s:6:"大小";s:1:"v";s:14:"10厘米以下";}}', #序列化后的SKU值 
                ),
        );
	 * @return array ( 
            [result] => success #订单处理结果
            [trade_no] => 88993062072938 #淘宝平台订单号
            [top_session] => 40815320f6b5418890685053251c21d58d7065ffD1avyu33635038291 #用户的TOP session 
            [pay_url] => http://trade.taobao.com/trade/pay.htm?biz_order_id=88993062072938&biz_type=200 #订单的支付链接，跳转到此地址可直接进入支付环节
            [order_id] => 1000505 #平台的订单ID 
            [alipay_no] => 2011081540308731 #支付宝平台订单号
      )
	 */
	public function createFanliOrder($params) {
		if (empty($params)) return false;
		if (empty($params['order']['post_type'])) $params['order']['post_type'] = 'express';
		return $this->getResult('manyi.order.fanli', array('data' => json_encode($params)));
	}
	
	
	/**
	 * 根据淘满意用户Id获取用户收货地址
	 * @param int $uid
	 * @param string $fields
	 */
	public function getUserAddress($taomanyiUid, $addressId = 0, $fields = '*') {
		$paramArr = array();
		$paramArr['pw_uid'] = $taomanyiUid;
		isset($paramArr['fields']) || $paramArr['fields'] = is_array($fields) ? implode(',', $fields) : $fields;
		$addresses = $this->getResult('manyi.user.address_get', $paramArr);
		
		if (!isset($addresses['total_results']) || $addresses['total_results'] < 1) return array();
		
		$resultAddresses = array();
		foreach ($addresses["user"]["addresses"] as $v){
			$resultAddresses[$v['address_id']] = $v;
		}
		
		if (!$addressId) return $resultAddresses;
		
		return isset($resultAddresses[$addressId]) ? $resultAddresses[$addressId] : array();
	}
	
	
	/**
	 * 根据淘满意用户Id添加收货地址
	 * @param int $taomanyiUid
	 * @param array $addressArray
	 */
	public function addUserAddress($taomanyiUid, array $addressArray) {
		//必填参数
		$needPrams = array('real_name', 'area', 'province', 'city', 'detail_address', 'zip_code');
		foreach ($needPrams as $v) {
			if (!array_key_exists($v, $addressArray)) {
				return 0;
			}
		}
		
		$paramArr = array();
		$paramArr['pw_uid'] = $taomanyiUid;
		$paramArr = array_merge($paramArr, $addressArray);
		
		$listArray = $this->getResult('manyi.user.address_add', $paramArr);
		
		if (is_array($listArray)) {
			return (int)$listArray['user']['address']['address_id'];
		}
		return 0;
	}
	
	/**
	 * 编辑用户收货地址
	 * @param int $addressId
	 * @param array $addressArray
	 */
	public function modifyUserAddress($addressId, $addressArray) {
		if (!is_numeric($addressId)) return false;
		//至少填一个： 'pw_uid','real_name','area','province','city','detail_address','zip_code','country','phone_ext','phone_num','phone_area_code','mobile','def_addr'
		$addArray = $this->getResult('manyi.user.address_edit', $addressArray);
		if(is_array($addArray) && $addArray['result']['msg'] === 'success'){
			return true;
		}
		return false;
	}

	/**
	 * 根据淘满意用户Id删除用户收货地址
	 * @param int $taomanyiUid
	 * @param int $addressId
	 */
	public function delUserAddress($taomanyiUid, $addressId) {
		if (!is_numeric($addressId)) return false;
		$addArray = $this->getResult('manyi.user.address_del', array('pw_uid' => $taomanyiUid, 'address_id' => $addressId));

		if (is_array($addArray) && $addArray['result']['msg'] === 'success') {
			return true;
		}
		return false;
	}
	
	/**
	 * 用户提现
	 * 
	 * @param int $siteId 站点id
	 * @param int $taomanyiUid
	 * @param array $params array('amount' => 提现金额, 'alipay_account' => '支付宝账户', 'alipay_account_name' => '支付宝账户实名')
	 */
	public function userAccountWithdrawWithSiteId($siteId, $taomanyiUid, array $params) {
		$paramArr = array();
		$paramArr['site_id'] = $siteId;
		$paramArr['pw_uid'] = $taomanyiUid;
		$paramArr = array_merge($paramArr, $params);
		return $this->getResult('manyi.account.withdraw', $paramArr);
	}
	
	
	/**
	 * 获取单个用户账户基本信息
	 * 
	 * @param int $taomanyiUid
	 */
	public function getUserAccount($taomanyiUid) {
		return $this->getUserAccounts(array($taomanyiUid));
	}
	
	
	/**
	 * 获取多个用户账户基本信息
	 * 
	 * @param int $taomanyiUid
	 */
	public function getUserAccounts(array $taomanyiUids) {
		$paramArr = array();
		$paramArr['pw_uid'] = implode(',', $taomanyiUids);
		return $this->getResult('manyi.account.get', $paramArr);
	}
	
	
	/**
	 * 用户账户变动明细
	 * 
	 * @param int $siteId 站点id
	 * @param int $taomanyiUid
	 * @param array $type 资金变动类型: consume-消费, withdraw-提现, fanli-收入
	 * @param array $status 变动明细当前状态:open=>进行中;locked=>锁定;closed=>完成;failure=>失败;settled=>结算
	 * @param int $page 页码
	 * @param int $perpage 每页记录数,默认为20，指定则最大可取值200
	 * @param int $beginTime 开始时间,默认为不限制
	 * @param int $endTime 结束时间，默认为： 1318780800//2011-10-17 00:00:00
	 * @return array
	 */
	protected function getUserAccountListWithSiteId($siteId, $taomanyiUid = null, array $type = array('consume', 'withdraw', 'fanli'), $status = null, $page = 1, $perpage = 20, $beginTime = 0, $endTime = '1318780800') {
		$paramArr = array();
		$paramArr['site_id'] = $siteId;
		if ($taomanyiUid) $paramArr['pw_uid'] = $taomanyiUid;
		$paramArr['type'] = implode(',', $type);
		if ($status && is_array($status)) $paramArr['status'] = implode(',', $status);
		$beginTime && $paramArr['begin_time'] = $beginTime;
		$endTime && $paramArr['end_time'] = $endTime;
		$paramArr['page_no'] = $page;
		$paramArr['page_size'] = $perpage;
		return $this->getResult('manyi.account.list', $paramArr);
	}
	
	/**
	 * 根据淘宝订单号获取站点佣金信息
	 * 
	 * @param int $tradeNo 淘宝订单号
	 * @return array
	 */
	public function getSingleFanliByTradeNo($tradeNo) {
		$paramArr = array();
		$paramArr['trade_no'] = $tradeNo;
		return $this->getResult('manyi.account.get_single_fanli', $paramArr);
	}
	
	/**
	 * 获得多个类目的淘客佣金比例
	 * @param string $cid
	 */
	public function getTaokeSubsidies(array $cids) {
		$paramArr = array();
		$paramArr['cid'] = implode(',', $cids);
		return $this->getResult('manyi.taoke.subsidy', $paramArr);
	}
	
	/**
	 * 获得单个类目淘客佣金比例
	 * @param string $cid
	 */
	public function getTaokeSubsidy($cid) {
		$result = $this->getTaokeSubsidies(array($cid));
		return isset($result['commission'][$cid]) ? $result['commission'][$cid] : 0;
	}
}