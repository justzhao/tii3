<?php

class Task_IndexController extends Desire_Application_Controller_Abstract
{
	public function indexAction() {
		Blaze_Factory::getInstance()->getCache()->file()->set('fd', time());
		echo Blaze_Factory::getInstance()->getCache()->file()->get('fd');
		Blaze_Factory::getInstance()->getCache()->file()->delete('fd');
	}
}