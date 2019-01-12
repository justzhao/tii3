<?php
/**
 * Controller abstract class with signature
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Abstract.php 6463 2016-08-11 15:18:28Z alacner $
 */

abstract class Tattoo_Application_Controller_Signature_Abstract extends Desire_Application_Controller_Abstract
{
	abstract protected function getSecretToken();

	public function error(Exception $e)
	{
		$this->assign("errcode", $e->getCode());
		$this->assign("errmsg", $e->getMessage());
	}

	public function init()
	{
		$this->noRender("json");

		$secretToken = $this->getSecretToken();
		if (empty($secretToken)) {
			throw new Desire_Application_Controller_Exception("invalid secret token");
		}

		$params = $this->getRequests();

		$tmpArr = array();
		foreach(array('signature', 'timestamp', 'nonce') as $key) {
			isset($params[$key]) || $tmpArr[] = $key;
		}

		if ($tmpArr) {
			throw new Desire_Application_Controller_Exception("invalid [%s]", implode(",", $tmpArr));
		}

		$now = Desire_Time::now();
		$expired = Desire_Config::get("tattoo.application.controller.signature.expired", 300);
		if (($params['timestamp'] > $now + $expired) ||($params['timestamp'] < $now - $expired)) {
			throw new Desire_Application_Controller_Exception("time error, server time [%s|%s], your time[%s|%s]",
				$now,
				Desire_Time::format('Y-m-d H:i:s', $now),
				$params['timestamp'],
				Desire_Time::format('Y-m-d H:i:s', $params['timestamp'])
			);
		}

		$tmpArr = array($secretToken, $params['timestamp'], $params['nonce']);
		sort($tmpArr, SORT_STRING); // use SORT_STRING rule
		$sign = sha1(implode($tmpArr));

		$_sign = Desire_Config::get('desire.debug_mode', false) ? $sign : substr_replace($sign, '~', 4, 24);
		$this->getResponse()->setHeader('Desire-Signature', $_sign);

		/*
		if ($sign !== $params['signature']) {//确保在nonce失效期期间的签名
			throw new Desire_Application_Controller_Exception("invalid signature");
		}
*/
		$nonceCacheKey = "desire.nonce." . Desire_Config::getIdentifier() . "." .$secretToken . "." . $params['nonce'];
		$nonce = Desire::object('Desire_Cache')->get($nonceCacheKey);

		if ($nonce) {
			throw new Desire_Application_Controller_Exception("nonce exist");
		} else {
			$nonce = Desire::object('Desire_Cache')->set($nonceCacheKey, 1, ($expired + 60));
		}

	}
}