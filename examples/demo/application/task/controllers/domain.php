<?php

class Task_DomainController extends Desire_Application_Controller_Abstract
{
	protected $header = array(
		'Referer' => 'http://domain.oray.com/check.php',
		'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36',
	);

	public function indexAction()
	{
		$digital = Desire_Config::get("app.task.domain.digital", array(0, 100));
		$char = Desire_Config::get("app.task.domain.char", array(0, 100));

		$t = array();
		$i = 'a';
		for ($n = $digital[0]; $n < $digital[1]; $n++) {
			$t[] = sprintf("%s", $n);
		}

		for ($j = 0; $j < $char[0]; $j++) ++$i;

		for ($n = $char[0]; $n < $char[1]; $n++) {
			$t[] = ++$i;
			//$t[] = sprintf("%03d", $i);
		}
		$domains = array_chunk($t, 5);
		$this->scanDomains($domains);
	}

	protected function scanDomains($domains)
	{
		foreach ($domains as $check) {
			$domains = $this->checkDomains($check, Desire_Config::get("app.task.domain.suffix", array('.com', '.net')));
			$data = array();
			foreach ($domains as $k => $v) {
				echo sprintf("%s => %s\n", $k, $v ? $this->getResponse()->colorize("available", "red") : 'not available.');
				$d = sprintf("%s => %s\n", $k, $v ? 'available' : 'not available.');
				if ($v) $data[] = $d;
			}
			file_put_contents(Desire_Config::get("app.task.domain.result_filename"), implode("", $data), FILE_APPEND);
		}
	}

	public function addPrefix(&$value, $key, $prefix)
	{
		$value = $prefix . $value;
	}

	protected function checkDomains($domain, $suffix = array('.com', '.net'))
	{
		is_array($domain) || $domain = array($domain);
		if (count($domain) > 5) {
			throw new Desire_Application_Controller_Exception('You can only five times per query');
		}

		$domains = array();
		foreach($domain as $d) {
			foreach($suffix as $f) $domains[] = sprintf("domain[]=%s%s", $d, $f);
		}

		$url = 'http://mcheck.oray.com:8082/domain/check?';
		$url .= implode("&", $domains);
		$url .= '&record=0&callback=jQuery17208481702588032931_1412380971880&_=1412380972040';

		$response = Desire_Http::get($url, $this->header);

		if ($response->state != 200) {
			throw new Desire_Application_Controller_Exception('Failed to obtain the data sources');
		}

		preg_match("/jQuery17208481702588032931_1412380971880\((.*)\);/", $response->data, $m);
		if (isset($m[1])) return json_decode($m[1], true);
		return array();
	}
}