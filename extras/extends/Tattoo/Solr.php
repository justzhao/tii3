<?php
/**
 * This is the PHP client for Solr 5.2.x- a search engine
 * Class using Desire_Http
 * More information is available at http://lucene.apache.org/solr
 * 
 * @author  Alacner Zhang <alacner@gmail.com>
 * @version $Id: Solr.php 6463 2016-08-11 15:18:28Z alacner $
 */

/**
 * Class SolrSchema
 * @see Apache Solr Reference Guide 5.2/P67+
 */
final class SolrSchema
{
	/**
	 * @var Tattoo_Solr
	 */
	private $solr;
	private $collectionName;

	public function __construct($solr, $collectionName)
	{
		$this->solr = $solr;
		$this->collectionName = $collectionName;
	}

	/**
	 * Schema API
	 * @param array $params
	 * @param array $formdata
	 * @param string $path
	 * @return mixed
	 */
	protected function invoke(array $params = array(), array $formdata = array(), $path = '')
	{
		return $this->solr->invoke(
			'/solr/' . $this->collectionName . '/schema'.$path.'?'.http_build_query($formdata),
			json_encode($params)
		);
	}

	/**
	 * @param string $path
	 * @return mixed
	 */
	public function get($path = '')
	{
		return $this->solr->invoke(
			'/solr/' . $this->collectionName . '/schema' . $path,
			'',
			false
		);
	}

	/**
	 * @param $fieldName
	 * @return mixed
	 */
	public function getFields($fieldName = '')
	{
		return $this->get($fieldName ? '/fields/'.$fieldName : '/fields');
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function getDynamicFields($name = '')
	{
		return $this->get($name ? '/dynamicfields/'.$name : '/dynamicfields');
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function getFieldTypes($name = '')
	{
		return $this->get($name ? '/fieldtypes/'.$name : '/fieldtypes');
	}

	/**
	 * @return mixed
	 */
	public function getCopyFields()
	{
		return $this->get('/copyfields');
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function getName($name)
	{
		return $this->get('/'.$name);
	}

	/**
	 * @return mixed
	 */
	public function getUniquekey()
	{
		return $this->get('/uniquekey');
	}

	/**
	 * @return mixed
	 */
	public function getSimilarity()
	{
		return $this->get('/similarity');
	}

	/**
	 * @return mixed
	 */
	public function getVersion()
	{
		return $this->get('/version');
	}

	/**
	 * @return mixed
	 */
	public function getDefaultOperator()
	{
		return $this->get('/solrqueryparser/defaultoperator');
	}

	/**
	 * @param $name
	 * @param $class
	 * @param array $options
	 * @return mixed
	 */
	public function addFieldType($name, $class, array $options = array())
	{
		return $this->invoke(
			array('add-field-type' => array_merge(array(
				'name' => $name,
				'class' => $class,
			), $options))
		);
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function deleteFieldType($name)
	{
		return $this->invoke(
			array('delete-field-type' => array('name' => $name))
		);
	}

	/**
	 * @param $name
	 * @param $class
	 * @param array $options
	 * @return mixed
	 */
	public function replaceFieldType($name, $class, array $options = array())
	{
		return $this->invoke(
			array('replace-field-type' => array_merge(array(
				'name' => $name,
				'type' => $class,
			), $options))
		);
	}

	/**
	 * @param [is_dynamic],name<string|array>,type,stored,indexed,required,multiValued,uniqueKey
	 * @return mixed
	 */
	public function addField()
	{
		$funcArgs = array_pad(func_get_args(), 8, false);

		$name = array_shift($funcArgs);

		if (is_bool($name)) {//is dynamic?
			$key = 'add-dynamic-field';
			$name = array_shift($funcArgs);
		} else {
			$key = 'add-field';
		}

		if (is_array($name)) {
			$values = $name;
		} else {
			list($type, $stored, $indexed, $required, $multiValued, $uniqueKey) = $funcArgs;

			$values = array('name' => $name, 'type' => $type);

			$stored && $values['stored'] = $stored;
			$indexed && $values['indexed'] = $indexed;
			$required && $values['required'] = $required;
			$multiValued && $values['multiValued'] = $multiValued;
			$uniqueKey && $values['uniqueKey'] = $uniqueKey;
		}

		return $this->invoke(
			array($key => $values)
		);
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	public function addFields(array $fields = array())
	{
		$result = array();
		foreach($fields as $field) {
			$result[] = $this->addField($field);
		}
		return $result;
	}

	/**
	 * @param [is_dynamic],name<string|array>,type,stored,indexed,required,multiValued,uniqueKey
	 * @return mixed
	 */
	public function replaceField()
	{
		$funcArgs = array_pad(func_get_args(), 8, false);

		$name = array_shift($funcArgs);

		if (is_bool($name)) {//is dynamic?
			$key = 'replace-dynamic-field';
			$name = array_shift($funcArgs);
		} else {
			$key = 'replace-field';
		}

		if (is_array($name)) {
			$values = $name;
		} else {
			list($type, $stored, $indexed, $required, $multiValued, $uniqueKey) = $funcArgs;

			$values = array('name' => $name, 'type' => $type);

			$stored && $values['stored'] = $stored;
			$indexed && $values['indexed'] = $indexed;
			$required && $values['required'] = $required;
			$multiValued && $values['multiValued'] = $multiValued;
			$uniqueKey && $values['uniqueKey'] = $uniqueKey;
		}

		return $this->invoke(
			array($key => $values)
		);
	}

	/**
	 * @param [is_dynamic]name
	 * @return mixed
	 */
	public function deleteField()
	{
		$funcArgs = array_pad(func_get_args(), 2, false);

		$name = array_shift($funcArgs);

		if (is_bool($name)) {//is dynamic?
			$key = 'delete-dynamic-field';
			$name = array_shift($funcArgs);
		} else {
			$key = 'delete-field';
		}

		return $this->invoke(array($key => array('name' => $name)));
	}

	/**
	 * param name<string|array>,type,stored,indexed,required,multiValued,uniqueKey
	 * @see addField
	 */
	public function addDynamicField()
	{
		$funcArgs = func_get_args();
		array_unshift($funcArgs, true);
		return call_user_func_array(array($this, 'addField'), $funcArgs);
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	public function addDynamicFields(array $fields = array())
	{
		$result = array();
		foreach($fields as $field) {
			$result[] = $this->addDynamicField($field);
		}
		return $result;
	}

	/**
	 * @see replaceField
	 */
	public function replaceDynamicField()
	{
		$funcArgs = func_get_args();
		array_unshift($funcArgs, true);
		return call_user_func_array(array($this, 'replaceField'), $funcArgs);
	}

	/**
	 * @see deleteField
	 */
	public function deleteDynamicField()
	{
		$funcArgs = func_get_args();
		array_unshift($funcArgs, true);
		return call_user_func_array(array($this, 'deleteField'), $funcArgs);
	}
}


/**
 * Class SolrSearch
 * @see Apache Solr Reference Guide 5.2/P67+
 */
final class SolrSearch
{
	/**
	 * @var Tattoo_Solr
	 */
	private $solr;
	private $collectionName;

	public function __construct($solr, $collectionName)
	{
		$this->solr = $solr;
		$this->collectionName = $collectionName;
	}

	/**
	 * Select API
	 * @param array $params
	 * @param array $formdata
	 * @param string $path
	 * @return mixed
	 */
	protected function invoke(array $params = array(), array $formdata = array(), $path = '')
	{
		$this->solr->paramsFilter($params);
		return $this->solr->invoke(
			'/solr/' . $this->collectionName . '/select'.$path.'?'.http_build_query($formdata),
			array($params)
		);
	}

	/**
	 * @param array $params
	 * @param array $formdata
	 * @return mixed
	 */
	public function select(array $params = array(), array $formdata = array())
	{
		return $this->invoke($params, $formdata);
	}
}


/**
 * Class SolrDocument
 * @see Apache Solr Reference Guide 5.2/P183+
 */
final class SolrDocument
{
	/**
	 * @var Tattoo_Solr
	 */
	private $solr;
	private $collectionName;

	public function __construct($solr, $collectionName)
	{
		$this->solr = $solr;
		$this->collectionName = $collectionName;
	}

	/**
	 * Select API
	 * @param array $params
	 * @param array $formdata
	 * @param string $path
	 * @return mixed
	 */
	public function invoke(array $params = array(), array $formdata = array(), $path = '')
	{
		return $this->solr->invoke(
			'/solr/' . $this->collectionName . '/update'.$path.'?'.http_build_query($formdata),
			json_encode($params)
		);
	}

	/**
	 * @param array $field
	 * @return mixed
	 */
	public function add(array $field = array())
	{
		return $this->invoke(array($field));
	}

	/**
	 * @param array $fields
	 * @return mixed
	 */
	public function adds(array $fields = array())
	{
		return $this->invoke($fields);
	}

	/**
	 * @param string $id
	 * @return mixed
	 */
	public function deleteById($id)
	{
		return $this->deleteByIds(array($id));
	}

	/**
	 * @param array $ids
	 * @return mixed
	 */
	public function deleteByIds(array $ids = array())
	{
		return $this->invoke(array("delete" => $ids));
	}
}

/**
 * Class Tattoo_Solr
 */
class Tattoo_Solr
{
	private $config = array(
		'gateway' => 'http://localhost:8983',
		'headers' => array(
			'Content-Type' => 'application/json'
		),
		'options' => array(
			'timeout' => 15,
		),
	);

	private $schemas = array();
	private $searches = array();
	private $documents = array();

	public function __construct(array $config = array())
	{
		$config || $config = Desire_Config::get("tattoo.solr", array());
		isset($config['headers']['Content-Type']) || $config['headers']['Content-Type'] = $this->config['headers']['Content-Type'];
		$this->config = array_merge($this->config, $config);
		$this->config['gateway'] = rtrim($this->config['gateway'], '/');
	}

	/**
	 * @param $path
	 * @param string $data
	 * @param bool $isPost
	 * @return mixed
	 * @throws Desire_Exception
	 */
	public function invoke($path, $data = '', $isPost = true)
	{
		$path = rtrim($path, '?');
		$path .= (strpos($path, '?') === false) ? '?wt=json' : '&wt=json';

		if ($isPost) {
			$response = Desire_Http::post(
				$this->config['gateway'] . $path,
				$data,
				$this->config['headers'],
				$this->config['options']
			);
		} else {
			$response = Desire_Http::get(
				$this->config['gateway'] . $path,
				$this->config['headers'],
				$this->config['options']
			);
		}

		if ($response->state == 404) {
			throw new Desire_Exception("invoke error, not found %s", $path);
		}
		return json_decode($response->data, true);
	}

	/**
	 * @param $collectionName
	 * @return SolrSchema
	 */
	public function getSchema($collectionName)
	{
		if (!isset($this->schemas[$collectionName])) {
			$this->schemas[$collectionName] = new SolrSchema($this, $collectionName);
		}
		return $this->schemas[$collectionName];
	}

	/**
	 * @param $collectionName
	 * @return SolrSearch
	 */
	public function getSearch($collectionName)
	{
		if (!isset($this->searches[$collectionName])) {
			$this->searches[$collectionName] = new SolrSearch($this, $collectionName);
		}
		return $this->searches[$collectionName];
	}

	/**
	 * @param $collectionName
	 * @return SolrDocument
	 */
	public function getDocument($collectionName)
	{
		if (!isset($this->documents[$collectionName])) {
			$this->documents[$collectionName] = new SolrDocument($this, $collectionName);
		}
		return $this->documents[$collectionName];
	}

	/**
	 * @param array $params
	 */
	public function paramsFilter(array &$params)
	{
		foreach($params as $name => $value) {
			($value === true) && $params[$name] = 'true';
		}
	}
}
