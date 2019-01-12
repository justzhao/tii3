<?php

class Blaze_Config
{
	/**
	 * 获取数据库中的配置
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return Ambigous <mixed, NULL>
	 */
	public function get($key, $default = null)
	{
		$value = $key;//some get value via key
		return is_null($value) ? $default : $value;
	}
}