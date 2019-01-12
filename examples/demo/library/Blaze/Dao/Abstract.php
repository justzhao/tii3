<?php

class Blaze_Dao_Abstract
{
	/**
	 * @return Desire_Dao
	 */
	public function getDao() {
		return Desire::object('Desire_Dao');
		//return Blaze_Factory::getInstance()->getDao();
	}

	/**
	 * @return Desire_Dao
	 */
	public function getSlaveDao() {
		return Desire::object('Desire_Dao', 'slave');//so you should config it at desire.database.slave
	}

	/**
	 * @return Desire_Dao
	 */
	public function getCustomerDao() {
		$config = array(//default config
			'dsn' => array(//@link http://php.net/manual/en/pdo.drivers.php
				'host' => 'localhost',
				'port' => 3306,
				'dbname'=> 'test',
			),
			'charset' => 'UTF8',
			'username' => 'root',
			'passwd' => '',
		);
		return Desire::object('Desire_Dao', $config);
	}
} 