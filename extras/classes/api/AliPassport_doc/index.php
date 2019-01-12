<?php

session_start();

require_once dirname(__FILE__) . '/library/Bootstrap.php';
require_once dirname(__FILE__) . '/AliPassport.php';
require_once dirname(__FILE__) . '/proAliPassport.class.php';


Passport::getInstance()->execute();

class Passport
{
	private static $instance = null;
	protected $request = null;
	
	public static function getInstance()
	{
		self::$instance || self::$instance = new self();
		return self::$instance;
	}

	public function execute()
	{
		$this->parameters = &$_REQUEST;

		array_key_exists('action', $this->parameters) || $this->parameters['action'] = 'index';
		try {
			$method = 'execute' . ucwords($this->parameters['action']);
			if (method_exists($this, $method)) {
				$response = call_user_func(array($this, $method));
				$response = array(true, $response);
			} else {
				$response = array(false, 'method not exists');
			}
		} catch (Exception $e) {
			$response = array(false, $e->getMessage());
		}

		echo json_encode($response);
	}
	
	protected function executeIndex()
	{
		$account = $this->parameters['account'];
		$password = $this->parameters['password'];
		$valicode = $this->parameters['valicode'];

		return proAliPassport::getInstance()->login($account, $password, $valicode);
	}

	public function executeValiCodeImage()
	{
		list ($response, $headers) = proAliPassport::getInstance()->getValiCodeImage();

		foreach ($headers as $protocol => $values) {
			is_array($values) || $values = array($values);

			foreach ($values as $value) {
				header($protocol . ':' . $value);
			}
		}

		echo $response->data;
		exit;
	}
}
