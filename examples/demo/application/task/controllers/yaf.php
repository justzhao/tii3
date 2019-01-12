<?php

class Task_YafController extends Desire_Application_Controller_Cli_Abstract
{
	public function indexAction() {

	}

	protected static $urls = array();
	protected static $existUrls = array();
	protected $savePath = 'M:/yaf';
	protected $root = 'http://yaf.laruence.com/manual/';
	protected $entryPage = 'http://yaf.laruence.com/manual/index.html';

	public function init() {
		is_dir($this->savePath) || mkdir($this->savePath, 0777, true);
		$this->preSync();
	}

	protected function preSync() {
		$response = Desire_Http::get($this->entryPage);
		preg_match_all('/href="(.*)"/iUs', $response->data, $match);

		if (!isset($match[1])) return false;
		foreach($match[1] as $url) {
			if (substr($url, 0, 7) == 'http://') continue;
			$url .= '#';
			$url = strstr($url, '#', true);
			self::$urls[] = $url;
		}
		self::$urls = array_unique(self::$urls);

		file_put_contents($this->savePath . '/index.html', $response->data);
		return true;
	}

	public function syncManualAction() {
		while($c = array_pop(self::$urls)) {
			printf("left:%d, exists:%d\n", count(self::$urls), count(self::$existUrls));
			if (in_array($c, self::$existUrls)) continue;
			$response = Desire_Http::get($this->root . $c);
			if (empty($response->data)) continue;
			preg_match_all('/href="#([^"]+)"/i', $response->data, $match);
			isset($match[1]) || $match[1] = array();
			foreach($match[1] as $c1) {
				if (in_array($c1, self::$existUrls)) continue;
				self::$urls[] = $c1;
			}
			foreach($match[1] as $c1) {
				if (substr($c1, 0, 7) == 'http://') continue;
				$c1 .= '#';
				$c1 = strstr($c1, '#', true);
				if (in_array($c1, self::$existUrls)) continue;
				self::$urls[] = $c1;
			}

			$content = preg_replace(array(
					'/<script.*<\/body>/iUs',
				),
				array(
					'</body>',
				),
				$response->data
			);
			file_put_contents($this->savePath . '/' . $c, $content);
			self::$existUrls[] = $c;
			//break;
		}
		$this->preChm();
	}
}