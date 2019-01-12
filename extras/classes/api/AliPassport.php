<?php
/**
 * 阿里内网域登录
 * @author Zhihua Zhang <alacner@gmail.com>
 * @version $Id: AliPassport.php 736 2012-07-20 00:35:03Z alacner $
 */

abstract class AliPassport
{
	abstract public function setSession($key, $value);
	abstract public function getSession($key);

	protected function setAttribute($key, $value)
	{
		$this->setSession('AliPassport_' . $key, serialize($value));
	}

	protected function getAttribute($key)
	{
		$attribute = $this->getSession('AliPassport_' . $key);
		return $attribute ? unserialize($attribute) : null;
	}

	public function getValiCodeImage()
	{
		$valiCodeImageCookie = $this->getAttribute('ValiCodeImageCookie');
		$header = $valiCodeImageCookie ? array('Cookie' => $valiCodeImageCookie) : array();

		$response = Desire_Http::get('https://passport.it.alibaba-inc.com/ValiCodeImage.ashx?k='.microtime(true), $header);

		$header || $this->setAttribute('ValiCodeImageCookie', $response->headers["Set-Cookie"]);

		$headers = array(
			'Cache-Control' => array(
				'private, max-age=0, no-store, no-cache, must-revalidate',
				'post-check=0, pre-check=0'
			),
			'Pragma' => 'no-cache',
			'Content-type' => 'image/png',
		);

		return array($response, $headers);
	}

	public function login($account, $password, $valicode)
	{
		$valiCodeImageCookie = $this->getAttribute('ValiCodeImageCookie');
		$header = $valiCodeImageCookie ? array('Cookie' => $valiCodeImageCookie) : array();

		$header = array_merge(array('Referer' => 'https://passport.it.alibaba-inc.com/login.ashx'), $header);

		$response = Desire_Http::post('https://passport.it.alibaba-inc.com/login.ashx', array(array(
			'account' => $account,
			'password' => $password,
			'valicode' => $valicode,
		)), $header);

		if ($response->state == 200) {
			preg_match('/<div class="message">(.*)<\/div>/iUs', $response->data, $error);
			if ($error) {
				throw new Exception($error[1]);
			}
		} else {
			throw new Exception($response->message);
		}

		parse_str(str_replace(',', '&', strstr($response->data, 'StaffId')), $userInfo);

		return $userInfo;
	}
}
