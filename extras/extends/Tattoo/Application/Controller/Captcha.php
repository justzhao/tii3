<?php
/**
 * 安全的验证码
 * 支持：验证码文字旋转，使用不同字体，可加干扰码、可加干扰线、可使用中文、可使用背景图片
 *
 * @author Alacner <alacner@gmail.com>
 * @version $Id: Captcha.php 488 2014-10-14 10:03:34Z alacner $
 */
class Tattoo_Application_Controller_Captcha extends Tattoo_Captcha_Abstract
{
	protected $controller = null;

	/**
	 * @return Tattoo_Application_Controller_Captcha
	 */
	public function setController($controller) {
		$this->controller = $controller;
		return $this;
	}
	
	protected function getController() {
		return $this->controller;
	}
	
	protected function saveCaptchaCode($key, $secode) {
		return $this->getController()->getResponse()->setSession($key, $secode);
	}
	
	protected function getCaptchaCode($key) {
		return $this->getController()->getSession($key);
	}

	protected function clearCaptchaCode($key)
	{
		return $this->getController()->getResponse()->setSession($key, null);
	}
}