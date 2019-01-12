<?php

/**
 * 抓取页面的links
 */
class Tattoo_PageLink
{
	public static function get($url){
		$response = Desire_Http::get($url);
		if ($response->state !== 200) return array();
		preg_match_all("/<a(s*[^>]+s*)href=([\"|']?)([^\"'>\s]+)([\"|']?)/ies", $response->data, $out);
		if (!isset($out[3])) return array();
		$arrLink = $out[3];
		$arrUrl = parse_url($url);
		if (isset($arrUrl['path']) && !empty($arrUrl['path'])) {
			$dir = str_replace('\\','/',$dir = dirname($arrUrl['path']));
			if( $dir == '/') $dir = '';
		}
	
		if(is_array($arrLink) && count($arrLink) > 0){
			$arrLink = array_unique($arrLink);
			foreach($arrLink as $key=>$val){
				if (preg_match('/^#.*$/isU', $val)) {
					$arrLink[$key] = $url . $val;
				} elseif (preg_match('/^\//isU', $val)) {
					$arrLink[$key] = 'http://'.$arrUrl['host'].$val;
				} elseif (preg_match('/^javascript/isU', $val)) {
					unset($arrLink[$key]);
				} elseif (preg_match('/^mailto:/isU', $val)) {
					unset($arrLink[$key]);
				} elseif(!preg_match('/^\//isU', $val) && strpos($val,'http://') === FALSE) {
					$arrLink[$key] = 'http://'.$arrUrl['host'].$dir.'/'.$val;
				}
			}
		}
		sort($arrLink);
		return $arrLink;
	}
}