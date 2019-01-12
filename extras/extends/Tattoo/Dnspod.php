<?php
/**
 *
 * Dnspod client
 * @see https://www.dnspod.cn/console/dns
 * 
 * @author  Alacner Zhang <alacner@gmail.com>
 * @version $Id: Dnspod.php 5572 2016-06-04 02:03:45Z alacner $
 */

class Tattoo_Dnspod
{
	private $config = [
		'gateway' => 'https://dnsapi.cn/',
		'headers' => [
			'UserAgent' => 'Tattoo Dnspod Client/1.0.0 (alacner@gmail.com)',
			'Content-Type' => 'application/json'
		],
		'format' => 'json',
	];

	public function __construct($config = [])
	{
		$this->config = array_merge($this->config, Desire_Config::get("tattoo.dnspod", []), $config);

		Desire::validator($this->config, [
			'login_token' => "not_empty"
		]);
	}

	public function __call($name, $arguments)
	{
		$data = Desire::valueInArray($arguments, 0, []);
		$data['login_token'] = $this->config['login_token'];
		$data['format'] = $this->config['format'];

		$response = Desire_Http::post(
			$this->config['gateway'] . str_replace('_', '.', ucwords($name, '_')),
			[$data],
			$this->config['headers']
		);

		$result = json_decode($response->data, true);

		if (!isset($result['status']['code']) || !in_array($result['status']['code'], [1])) {
			throw new Desire_Exception(Desire::valueInArray($result['status'], 'message', $response->data));
		}

		return $result;
	}
}
