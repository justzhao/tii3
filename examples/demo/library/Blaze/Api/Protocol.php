<?php
class Blaze_Api_Protocol extends Desire_Api_Protocol_Abstract
{
	protected static $baseHash = 'lBJNXImJRUcj1egPCzqmLaVw7YBf1nb4';
	
	public static function getSaltHash($hash) {
		return self::$baseHash . $hash;
	}
}