<?php

class Captcha_IndexController extends Desire_Application_Controller_Abstract
{
	public function indexAction() {
		$this->noRender();
		$id = $this->getQuery('from', 'default');
		$captcha = Desire::object("Tattoo_Application_Controller_Captcha")->setController($this);
		$this->getResponse()->setHeaders($captcha->getHeaders());
		$captcha->entry($id);
	}
}