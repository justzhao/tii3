<?php

/**
 * 包装返回值
 *
 * Class Tattoo_Result
 */
class Tattoo_Result
{
	public $successed = false;
	public $errorNo;
	public $errorMessage;
	public $data;
	public $now;

	public function __construct()
	{
		$this->now = Desire_Time::format();
	}

	public function setError($no, $err)
	{
		$this->successed = false;
		$this->errorNo = $no;
		$this->errorMessage = $err;
	}

	public function setData($data)
	{
		$this->data = $data;
		$this->successed = true;
	}

	public function isSuccessed()
	{
		return $this->successed;
	}

	public function getErrorNo()
	{
		return $this->errorNo;
	}

	public function getErrorMessage()
	{
		return $this->errorMessage;
	}

	public function getData()
	{
		return $this->data;
	}

	public function getNow()
	{
		return $this->now;
	}
} 