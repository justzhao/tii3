<?php

class Task_WorkermanController extends Desire_Application_Controller_Abstract
{
	protected $baseUrl = 'http://doc3.workerman.net';

	protected $existUrls = [];
	protected $urls = [
		'/index.html'
	];
	protected $files = [];
	protected $fullUrls = [];


	protected $patterns = [];
	protected $replacements = [];

	protected $savePath = '/workspace/desire/framework/extras/classes/workerman/docs';

	public function indexAction()
	{
		while($url = array_pop($this->urls)) {
			$this->println('url cnt:%d, existUrls cnt:%d, url:%s', count($this->urls), count($this->existUrls), $url);
			$this->crawl($url);
		}

		while($url = array_pop($this->files)) {
			$this->println('file:%d, url:%s', count($this->files), $url);
			$this->dl($url);
		}

		while($url = array_pop($this->fullUrls)) {
			$this->println('fullUrls:%d, url:%s', count($this->fullUrls), $url);
			$this->dlf($url);
		}

		//replace
		foreach(Desire_Filesystem::getFiles($this->savePath, ["html"]) as $f) {
			$this->println('replace: %s', $f);
			$con =  file_get_contents($f);
			$con =  str_replace($this->patterns, $this->replacements, $con);
			file_put_contents($f, $con);
		}

		file_put_contents('/readme', Desire_Time::format());
	}

	protected function dl($url)
	{
		$response = Desire_Http::get($this->baseUrl . $url);
		if ($response->state != '200') return;

		$this->save($url, $response->data);
	}

	protected function dlf($url)
	{
		$response = Desire_Http::get('http:/' . $url);
		if ($response->state != '200') return;

		$this->patterns[] = 'http:/' . $url;
		$this->replacements[] = '..' . $url;

		$url2 = strstr($url, '?', true);
		$url = $url2 ?: $url;
		$this->save($url, $response->data);
	}

	protected function crawl($url)
	{
		$this->existUrls[$url] = true;

		$response = Desire_Http::get($this->baseUrl . $url);
		if ($response->state != '200') return;

		$this->save($url, $response->data);

		preg_match_all('|<link[^>]+href="\.(/[^"]+)"|iUs', $response->data, $m);
		if ($m[1]) {
			foreach($m[1] as $f) {
				if ($this->existUrls[$f]) continue;
				$this->existUrls[$f] = true;
				$this->files[] = $f;
			}
		}
		unset($m);

		preg_match_all('|<link[^>]+href="(gitbook/[^"]+)"|iUs', $response->data, $m);
		if ($m[1]) {
			foreach($m[1] as $f) {
				$f = '/' . $f;
				if ($this->existUrls[$f]) continue;
				$this->existUrls[$f] = true;
				$this->files[] = $f;
			}
		}
		unset($m);

		preg_match_all('|<script[^>]+src="(gitbook/[^"]+)"|iUs', $response->data, $m);
		if ($m[1]) {
			foreach($m[1] as $f) {
				$f = '/' . $f;
				if ($this->existUrls[$f]) continue;
				$this->existUrls[$f] = true;
				$this->files[] = $f;
			}
		}
		unset($m);

		preg_match_all('|<a[^>]+href="\.(/[^"]+)">|iUs', $response->data, $m);
		if ($m[1]) {
			foreach($m[1] as $f) {
				if ($this->existUrls[$f]) continue;
				$this->existUrls[$f] = true;
				$this->urls[] = $f;
			}
		}
		unset($m);

		preg_match_all('|<img[^>]+src="http:/(/[^"]+)"|iUs', $response->data, $m);
		if ($m[1]) {
			foreach($m[1] as $f) {
				if ($this->existUrls[$f]) continue;
				$this->existUrls[$f] = true;
				$this->fullUrls[] = $f;
			}
		}
		unset($m);

		preg_match_all('|<script[^>]+src="http:/(/[^"]+)"|iUs', $response->data, $m);
		if ($m[1]) {
			foreach($m[1] as $f) {
				if ($this->existUrls[$f]) continue;
				$this->existUrls[$f] = true;
				$this->fullUrls[] = $f;
			}
		}

	}

	protected function save($path, $data)
	{
		$path = $this->savePath . $path;
		Desire_Filesystem::mkdir(dirname($path));
		file_put_contents($path, $data);
	}
}