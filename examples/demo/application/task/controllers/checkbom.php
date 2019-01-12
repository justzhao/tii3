<?php

class Task_CheckbomController extends Desire_Application_Controller_Abstract
{
	private $auto = 0;
	private $debug = 0;

	public function indexAction()
	{
		$basedir = $this->getEnv("basedir");
		if (!$basedir) die("Usage: php shell task checkbom --basedir=/path/to/check [--auto][--debug]\n");
		$this->auto = $this->getEnv("auto", 0);
		$this->debug = $this->getEnv("debug", 0);

		echo "check bom at $basedir , auto $this->auto ...\n";
		$this->checkdir($basedir);
	}

	public function clearAction()
	{
		$filename = $this->getEnv("filename");
		if (!$filename) die("Usage: php shell task checkbom clear --filename=/path/to/filename\n");

		$this->checkBOM($filename, 1, true);
	}

	private function checkdir($basedir)
	{
		if ($dh = opendir($basedir)) {
			while (($file = readdir($dh)) !== false) {
				if ($file != '.' && $file != '..'){
					if (!is_dir($basedir."/".$file)) {
						$this->checkBOM("$basedir/$file", $this->auto, $this->debug);
					}else{
						$dirname = $basedir."/".$file;
						$this->checkdir($dirname);
					}
				}
			}
			closedir($dh);
		}
	}

	private function rewrite ($filename, $data)
	{
		$filenum = fopen($filename, "w");
		flock($filenum, LOCK_EX);
		fwrite($filenum, $data);
		fclose($filenum);
	}

	private function checkBOM ($filename, $auto = 0, $debug = false)
	{
		$contents = file_get_contents($filename);
		$charset[1] = substr($contents, 0, 1);
		$charset[2] = substr($contents, 1, 1);
		$charset[3] = substr($contents, 2, 1);
		if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
			echo "filename $filename BOM found ... ";
			if ($auto == 1) {
				$rest = substr($contents, 3);
				$this->rewrite ($filename, $rest);
				echo "automatically removed";
			}
			echo "\n";
		} else {
			if ($debug) {
				echo "filename $filename BOM Not Found.\n";
			}
		}
	}

}
