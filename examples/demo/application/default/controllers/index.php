<?php

class Default_IndexController extends Desire_Application_Controller_Abstract
{
	public function init() {
		//$this->setLayout('frontend');
	}
	
	public function indexAction() {
		$this->noRender();
	}
}