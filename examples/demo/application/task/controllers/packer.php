<?php

class Task_PackerController extends Desire_Application_Controller_Abstract
{
	public function indexAction() {
		// 打包某个目录下的所有文件
		$packer = new Tattoo_Packer();
		$packer->setDirectory(DESIRE_DIRECTORY);
		$packer->setCacheDirectory(DESIRE_DIRECTORY . '/../build');
		$packer->exclude('Autoloader.php');
		$packer->setPackOrder('Bootstrap.php', 'Version.php', 'Autoloader.php');
		$packer->setPackName('Desire-' . Desire_Version::VERSION);
		$packer->loader();
	}
}