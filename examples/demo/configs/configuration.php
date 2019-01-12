<?php
define('CONFIGURATION_DIRECTORY', dirname(__FILE__));

if (ini_get('apc.enabled')) {
	require_once CONFIGURATION_DIRECTORY . '/../../build/Desire-0.14.926.958.Packer.php';//加载 desire 打包库

} else {
	require_once CONFIGURATION_DIRECTORY . '/../../library/Bootstrap.php';//加载 desire 库
}

Desire_Config::setDir(CONFIGURATION_DIRECTORY);// 修改配置文件的路径