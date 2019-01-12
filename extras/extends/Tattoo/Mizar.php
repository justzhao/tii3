<?php
/**
 * Class Tattoo_Mizar
 */

class _Tattoo_Mizar_Delegate
{
	protected $dsn;
	protected $registry;
	protected $name;
	protected $version;
	protected $client;

	public function __construct($dsn, $registry, $name, $version)
	{
		$this->dsn = $dsn;
		$this->registry = $registry;
		$this->name = $name;
		$this->version = $version;
		$this->client = new Desire_Worker_Client($dsn);
	}

	public function __call($name, $arguments)
	{
		$result = $this->client->request([
			'registry' => $this->registry,
			'dsn' => $this->dsn,
			'name' => $this->name,
			'version' => $this->version,
			'methodName' => $name,
			'args' => $arguments
		]);

		if (isset($result['errmsg'])) {
			throw new Desire_Exception($result['errmsg']);
		}

		return $result;
	}
}

class _Tattoo_Mizar_JavaDelegate extends _Tattoo_Mizar_Delegate
{
	public function __call($name, $arguments)
	{
		$passthrough = false;

		$tmp = array_pop($arguments);
		if (isset($tmp['$types'])) {
			$paramTypes = $tmp['$types'];
		} else {
			$arguments[]  = $tmp;
			$paramTypes = array();
		}

		for($i = 0, $j = count($arguments); $i < $j; $i++) {
			if (isset($paramTypes[$i])) continue;

			$paramType = gettype($arguments[$i]);
			$paramTypes[$i] = in_array($paramType, array("boolean", "integer", "double", "string")) ? $paramType : "object";
		}

		foreach($paramTypes as &$paramType) {
			if (in_array($paramType, array("boolean", "integer", "double", "string", "long", "int", "object"))) {
				$paramType = 'Ljava/lang/'.ucfirst($paramType);
			}
		}

		//passthrough
		if ($name{0} == '_') {
			$passthrough = true;
			$name = substr($name, 1);
		}

		$result = $this->client->request([
			'registry' => $this->registry,
			'dsn' => $this->dsn,
			'name' => $this->name,
			'version' => $this->version,
			'methodName' => $name,
			'args' => $arguments,
			'parameterTypes' => $paramTypes,
		]);

		if ($passthrough) return $result;
		Desire_Logger::debug(__METHOD__, $result);
		if (!Desire::valueInArray($result, 'success', false)) {
			throw new Desire_Exception(Desire::valueInArray($result, 'error', 'invoker response error'));
		}
		return Desire::valueInArray($result, 'data', NULL);
	}
}

class Tattoo_Mizar
{
	private $registry;
	private $register;
	private $reference;
	private $delegate;

	public function __construct($registry = NULL, $reference = NULL, $delegate = '_Tattoo_Mizar_Delegate')
	{
		is_null($registry) || $registry = Desire::get('tattoo.mizar.registry', 'text.json://127.0.0.1:9527');
		is_null($reference) || $reference = Desire::get('tattoo.mizar.reference', []);

		$this->registry = $registry;
		$this->register = new Desire_Worker_Client($registry);
		$this->reference = $reference;
		$this->delegate = $delegate;
	}

	public function getService($name = 'default', $version = '0.0.0')
	{
		static $clients = [];

		if (!isset($clients[$name])) {

			$res = $this->register->request(array_merge(
				$this->reference,
				[
					'cmd' => 'hunt',
					'name' => $name,
					'version' => $version,
					'timestamp' => Desire_Time::format(),
				]
			));

			if ($res['status'] == 'ok') {
				$clients[$name] = new $this->delegate($res['data'], $this->registry, $name, $version);
			} else {
				throw new Desire_Exception($res['data']);
			}
		}

		return $clients[$name];
	}
}