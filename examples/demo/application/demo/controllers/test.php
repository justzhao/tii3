<?php

class Demo_TestController extends Desire_Application_Controller_Abstract
{
	public function init()
	{
		echo '<br/>test init';
		$this->assign('now', time());
	}
	
	public function indexAction()
	{
		
		print_r($this->getRequests());
	}
	
	public function testAction()
	{
		echo '<br/>test test action';
	}
	
	public function over()
	{
		echo '<br/>test action over';
	}
}