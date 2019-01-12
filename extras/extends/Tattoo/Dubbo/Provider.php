<?php

/**
 * Class Tattoo_Dubbo_Registry_Zookeeper
 */
class Tattoo_Dubbo_Provider
{
	private $args = array();//registry,serviceName,serviceVersion,serviceGroup,serviceToken

	public function __construct(array $args = array())
	{
		$this->args = $args;
	}

	/**
	 * @param $name
	 * @param $args
	 * @return array|mixed|string
	 */
	public function __call($name, $args)
	{
		$passthrough = false;
		$tmp = array_pop($args);
		if (isset($tmp['$types'])) {
			$paramTypes = $tmp['$types'];
		} else {
			$args[]  = $tmp;
			$paramTypes = array();
		}

		for($i = 0, $j = count($args); $i < $j; $i++) {
			if (isset($paramTypes[$i])) continue;

			$paramType = gettype($args[$i]);
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

		$request = new Tattoo_Dubbo_Request(array_merge($this->args, array(
			'method' => $name,
			'args' => $args,
			'paramTypes' => $paramTypes,
		)));

		return $passthrough ? $request->invoke() : $request->invoker();
	}
}