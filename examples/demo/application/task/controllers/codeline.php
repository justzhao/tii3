<?php

class Task_TestController extends Desire_Application_Controller_Abstract
{
	protected $exts = array('.php', '.css', '.js', '.html', '.phtml', '.htm', '.txt', '.sql');

	public function indexAction()
	{

		$paths = array(
			'yii' => 'path',
		);


		$lines = array();
		foreach($paths as $name => $path) {
			$line = $this->codeline($path);
			$lines[$name] = $line;
			$lines['total'] += $line;
		}
		print_r($lines);
	}

	protected function codeline($dir)
	{
		$number = 0;
		if (!is_dir($dir)) return $number;
		$files  = scandir($dir);
		foreach($files as $file) {
			print_r("$dir\n");
			if ($file === '.' || $file === '..') continue;
			$file =  $dir . '/' . $file;
			if (is_dir($file)) {
				$number += self::codeline($file);
			}
			else {
				$ext = strrchr($file, '.');
				if (!in_array($ext, $this->exts)) continue;
				$lines = file($file);
				$number += count($lines);
				unset($lines);
			}
		}
		return $number;
	}
}