<?php

class Test_ErrorController extends Desire_Application_Controller_ErrorAbstract
{
	public function errorAction()
	{
		if (Desire_Config::isDebugMode()) {
			$this->setRender('debug_mode');
		}
	}
}
