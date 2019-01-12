<?php

class Task_LogoController extends Desire_Application_Controller_Abstract
{
	public function indexAction()
	{
		$data = file_get_contents('m:/dfsa-3.222png.png');
		$images = array();
		for ($i = 0, $j = strlen($data); $i < $j; $i++) {
			$images[] = '0x' . bin2hex($data[$i]);
		}
		$images = array_chunk($images, 12);
		$images2 = array();
		foreach($images as $image) {
			$images2[] = "  " . implode(', ', $image);
		}
		echo implode(",\n", $images2);
	}
}