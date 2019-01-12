<?php
/**
 * AliPassport
 */

class proAliPassport extends AliPassport
{
	private static $instance;

	/**
	 * @return proAliPassport
	 */
	public static function getInstance()
	{
		self::$instance || self::$instance = new self();
		return self::$instance;
	}

	public function setSession($key, $value)
	{
		return $_SESSION[$key] = $value;
	}

	public function getSession($key)
	{
		return $_SESSION[$key];
	}
}
