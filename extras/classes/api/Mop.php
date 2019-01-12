<?php

require_once DESIRE_DIRECTORY . '/Utilities/MopClient.php';

class MopApi extends MopClient
{
	/**
	 * 缺省方法
	 * 替换mop接口方法中的.为_
	 * MopClient::getInstance()->user_get(array(应用级输入参数));
	 */
	public function __call($functionName, $arguments) {
		if (strpos($functionName, '_') === false) return false;
		$arguments[0] = new _MopApiMethod(str_replace('_', '.', $functionName), $arguments[0]);
		return call_user_func_array(array($this, 'execute'), $arguments);
	}
}