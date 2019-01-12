<?php

class SinaApi
{
	/**
	 * 获取短域名接口
	 * @param string $longUrl
	 */
	private function getShortUrlApi($longUrl)
	{
		$apiUrl = 'http://api.t.sina.com.cn/short_url/shorten.json?source=744243473&url_long='.urlencode($longUrl);
		$result = Desire_Http::get($apiUrl);
		if ($result->state !== 200) $result->data = '';
		$result = json_decode($result->data);
		return (isset($result[0]) && !empty($result[0]->url_short)) ? $result[0]->url_short : null;
	}
	
	/**
	 * 长域名获的新浪的短域名地址
	 * @param string $longUrl
	 */
	public function getShortUrl($longUrl)
	{
		if (empty($longUrl)) return null;
		
		for ($i = 0; $i < 3; $i++) {//try 3 times
			$shortUrl = $this->getShortUrlApi($longUrl);
			if ($shortUrl) return $shortUrl;
		}
		return $longUrl;
	}
}