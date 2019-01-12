<?php

class Blaze_Dao_Demo extends Blaze_Dao_Abstract
{
	private $tablename = 'some_table_name';

	public function addsomething() {
		return $this->getDao()->getQueryHelper()->insert($this->tablename, array('columnName1' => 'value1', 'columnName2' => 'value2'));
	}
} 