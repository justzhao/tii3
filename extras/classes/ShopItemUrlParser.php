<?php
class ShopItemUrlParser {
	
	/**
	 * 传入url，返回商品id及所在网站
	 * @param string $url
	 * @return array('site' => ?, 'id' => ?);
	 */
	public static function get($url) {
		$return = array(
			'site' => null,
			'id' => null,
			'class' => null,
		);
		
		$parseUrl = parse_url($url);
		if (!$parseUrl || !array_key_exists('host', $parseUrl)) return $return;
		// 淘宝
		if (preg_match('/.*\.taobao\.com$/iU', $parseUrl['host'])) {
			$return['class'] = 'alibaba';
			$return['site'] = 'taobao.com';
			if (!array_key_exists('query', $parseUrl)) return $return;
			$return['id'] = self::forTaobao($parseUrl['query']);
			$return['id'] && $return['click'] = sprintf('http://item.taobao.com/item.htm?id=%s', $return['id']);
			return $return;
		}
		
		// 淘宝商城
		if (preg_match('/.*\.tmall\.com$/iU', $parseUrl['host'])) {
			$return['class'] = 'alibaba';
			$return['site'] = 'tmall.com';
			if (!array_key_exists('query', $parseUrl)) {
				$return['id'] = self::forTmallSpu($url);
			} else {
				$return['id'] = self::forTaobao($parseUrl['query']);
				$return['id'] || $return['id'] = self::forTmallSpu($url);
			}
			
			$return['id'] && $return['click'] = sprintf('http://item.tmall.com/item.htm?id=%s', $return['id']);
			return $return;
		}
		
		// hitao
		if (preg_match('/.*\.hitao\.com$/iU', $parseUrl['host'])) {
			$return['class'] = 'alibaba';
			$return['site'] = 'hitao.com';
			if (!array_key_exists('path', $parseUrl)) return $return;
			$return['id'] = self::forHitao($parseUrl['path']);
			$return['id'] && $return['click'] = sprintf('http://mall.hitao.com/item-%s.htm', $return['id']);
			return $return;
		}
		
		// 淘花网
		if (preg_match('/.*\.taohua\.com$/iU', $parseUrl['host'])) {
			$return['class'] = 'alibaba';
			$return['site'] = 'taohua.com';
			if (!array_key_exists('query', $parseUrl)) return $return;
			$return['id'] = self::forTaobao($parseUrl['query']);
			$return['id'] && $return['click'] = sprintf('http://item.taohua.com/item.htm?id=%s', $return['id']);
			return $return;
		}
		
		// 趣淘返利
		if (preg_match('/.*fanli\.qutao\.com$/iU', $parseUrl['host'])) {
			$return['class'] = 'alibaba';
			$return['site'] = 'qutao.com';
			if (!array_key_exists('query', $parseUrl)) return $return;
			$return['id'] = self::forTaobao($parseUrl['query']);
			$return['id'] && $return['click'] = sprintf('http://fanli.qutao.com/item?id=%s', $return['id']);
			return $return;
		}
		
		// 淘宝日常
		if (preg_match('/.*\.daily\.taobao\.net$/iU', $parseUrl['host'])) {
			$return['class'] = 'alibaba';
			$return['site'] = 'taobao.com';
			if (!array_key_exists('query', $parseUrl)) return $return;
			$return['id'] = self::forTaobao($parseUrl['query']);
			$return['id'] && $return['click'] = sprintf('http://item.daily.taobao.net/item.htm?id=%s', $return['id']);
			return $return;
		}
		
		// 卓越
		if (preg_match('/.*\.amazon\.cn$/iU', $parseUrl['host'])) {
			$return['class'] = 'amazon';
			$return['site'] = 'amazon.cn';
			if (!array_key_exists('path', $parseUrl)) return $return;
			$return['id'] = self::forAmazon($parseUrl['path']);
			return $return;
		}
		
		// 京东
		if (preg_match('/.*\.360buy\.com$/iU', $parseUrl['host'])) {
			$return['class'] = '360buy';
			$return['site'] = '360buy.com';
			if (!array_key_exists('path', $parseUrl)) return $return;
			$return['id'] = self::for360buy($parseUrl['path']);
			return $return;
		}
		
		return $return;
	}
	
	/**
	 * 提取淘宝的商品id
	 * 
	 * http://item.taobao.com/auction/item_detail.htm?item_num_id=1856942065
	 * http://item.taobao.com/item.htm?id=1856942065
	 */
	public static function forTaobao($str) {
		parse_str($str, $params);
		if (array_key_exists('item_num_id', $params)) return $params['item_num_id'];
		if (array_key_exists('id', $params)) return $params['id'];
		if (array_key_exists('mallstItemId', $params)) return $params['mallstItemId'];
		if (array_key_exists('item_id', $params)) return $params['item_id'];
		return null;
	}
	
	/**
	 * Spu 商品转成淘宝item id
	 * //http://list.3c.tmall.com/spu-96282322.htm?prc=1
	 * //http://spu.tmall.com/spu_detail.htm?prc=1&spu_id=119042808
	 */
	public static function forTmallSpu($url) {
		if (stripos($url, 'spu') === false) return null;
		$response = Desire_Http::get($url);
		if ($response->state != 200) return null;
		preg_match('/CPS\.trace\(\{.*itemid:"(\d+)".*\}\);/iUs', $response->data, $m);
		if (isset($m[1])) return $m[1];
		return null;
	}
	
	/**
	 * 提取hitao的商品id
	 * @param unknown_type $str
	 * http://mall.hitao.com/item-10081870067.htm
	 */
	public static function forHitao($str) {
		preg_match('/\/item-(\d+)\.htm/iU', $str, $match);
		return isset($match[1]) ? $match[1] : null;
	}
	
	/**
	 * 提取joyo、amazon的商品id
	 * 
	 * @param string $str
	 * http://www.amazon.cn/%E6%91%A9%E6%89%98%E7%BD%97%E6%8B%89ME525-%E6%99%BA%E8%83%BD3G%E6%89%8B%E6%9C%BA/dp/B004HKJTUG/ref=sr_1_1?s=wireless&ie=UTF8&qid=1312690955&sr=1-1
	 */
	public static function forAmazon($str) {
		preg_match('/\/dp\/(.+)\//iU', $str, $match);
		return isset($match[1]) ? $match[1] : null;
	}
	
	
	
	/**
	 * 提取京东的商品id
	 * 
	 * @param string $str
	 * http://www.360buy.com/product/357161.html
	 */
	public static function for360buy($str) {
		preg_match('/product\/(\d+)\.html/iU', $str, $match);
		if(isset($match[1])) return $match[1];
		preg_match('/p(\d+)\.html/iU', $str, $match);
		return isset($match[1]) ? $match[1] : null;
	}
}