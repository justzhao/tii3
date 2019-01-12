<?php
/**
 * 点击助手
 *
 */
class Blaze_Controller_Helper_Click extends Desire_Controller_Helper_Abstract
{
	public function click($url) {
		$url = Desire_Security_Encryption::encode($url);
		return Desire_Config::get('app.domain.www') . '/url/click?' . urlencode(base64_encode($url));
	}
}