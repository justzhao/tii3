<?php

/**
 * 日志记录
 * @author Yametei
 * @version $Id: Logger.php 6770 2016-08-30 12:38:15Z alacner $
 */
class Blaze_Logger extends Desire_Logger_Abstract
{
	protected static $instance = null;

	/**
	 * @return Blaze_Logger
	 */
	public static function getInstance(){
		is_object(self::$instance) || self::$instance = new self();
		return self::$instance;
	}
	
	public function doLog($message, $priority = self::ERR, $extras = null) {
		$fieldsData = array();
		$fieldsData['priority'] = $priority;
		$fieldsData['message'] = var_export($message, true);
		$fieldsData['created_at'] = Desire_Time::now();
		
		$this->getEngineLoggerService()->add($fieldsData);
	}
	
	/**
	 * @return Blaze_Models_Engine_Dao_Logger
	 */
	private function getEngineLoggerService() {
		return Blaze_Models_Engine_Service_Factory::getInstance()->createLoggerService();
	}
	
}