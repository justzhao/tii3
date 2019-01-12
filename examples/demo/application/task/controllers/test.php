<?php

class Task_TestController extends Desire_Application_Controller_Abstract
{
	public function indexAction()
	{
		$t = Desire_Http::get('http://www.baidu.com');
		print_r($t);
	}

	public function nexttimeAction()
	{
		$nexttime = Desire_Time::nexttime('*/3 4-7,23 * * *');
		var_dump($nexttime);
	}

	public function paramsAction()
	{
		//php shell task test params --xxx=xx2 --y=hhh
		/**
		 *
		Array
		(
		[xxx] => xx2
		[y] => hhh
		)
		 */
		print_r($this->getEnvs());
	}
}
