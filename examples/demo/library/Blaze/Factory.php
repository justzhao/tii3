<?php
/**
 * 引擎模块的缓存实现类 hank said: 不推荐使用
 * @author Yametei
 * @deprecated
 * @version $Id: Factory.php 6770 2016-08-30 12:38:15Z alacner $
 */
class Blaze_Factory
{
	protected static $instance = null;

	/**
	 * @return Blaze_Factory
	 */
	public static function getInstance()
	{
		self::$instance || self::$instance = new self();
		return self::$instance;
	}

	/**
	 * @return Desire_Cache
	 */
	public function getCache()
	{
		return Desire::object('Desire_Cache');
	}

	/**
	 * @return Desire_Dao
	 */
	public function getDao()
	{
		return Desire::object('Desire_Dao');
	}

	/**
	 * @return Desire_Hook
	 */
	public function getHook()
	{
		return Desire::object(
			'Desire_Hook',
			Desire_Config::get('library_dir') . '/Hook',
			Desire_Config::get('desire.temp_dir'),
			Desire_Config::get('desire.debug_mode')
		);
	}
}