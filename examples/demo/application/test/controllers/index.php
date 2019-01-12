<?php

class Test_IndexController extends Desire_Application_Controller_Abstract
{

	public function indexAction()
	{
		$this->assign('time', Desire_Time::format());
        $this->setRenderCache(5);
	}

	public function layoutAction()
	{
		$this->setLayout('layout');
	}

	public function forwardAction()
	{
		$this->forward('layout');
	}

	public function testAction()
	{
		$this->noRender();
		print_r($this->getRequestMethod());
		print_r($this->getPairs());
		$this->redirect('/', 6);
	}
}