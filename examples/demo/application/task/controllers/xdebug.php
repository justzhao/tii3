<?php

class Task_XdebugController extends Desire_Application_Controller_Abstract
{
	public function indexAction() {

	}

	protected static $urls = array();
	protected static $existUrls = array();
	protected $savePath = 'M:/xdebug';
	protected $root = 'http://xdebug.org';
	protected $entryPage = 'http://xdebug.org/docs/';

	public function init() {
		is_dir($this->savePath) || mkdir($this->savePath, 0777, true);
		$this->preSync();
	}

	protected function preSync() {
		$response = Desire_Http::get($this->entryPage);
		preg_match_all("/href='(\/docs\/.*)'/iUs", $response->data, $match);

		if (!isset($match[1])) return false;
		foreach($match[1] as $url) {
			if (substr($url, 0, 7) == 'http://') continue;
			$url .= '#';
			$url = strstr($url, '#', true);
			self::$urls[] = $url;
		}
		preg_match_all("/href=\"(\/docs\/.*)\"/iUs", $response->data, $match);

		if (!isset($match[1])) return false;
		foreach($match[1] as $url) {
			if (substr($url, 0, 7) == 'http://') continue;
			$url .= '#';
			$url = strstr($url, '#', true);
			self::$urls[] = $url;
		}
		self::$urls = array_unique(self::$urls);

		$content = preg_replace(array(
				'/<div id="support".*<\/div>/iUs',
			),
			array(
				'',
			),
			$response->data
		);

		$content = preg_replace("/href='(\/docs\/[^#']+)/ie", "\$this->getHref('\\1')", $content);
		$content = preg_replace("/href=\"(\/docs\/[^#\"]+)/ie", "\$this->getHref2('\\1')", $content);


		file_put_contents($this->savePath . '/index.html', $content);
		return true;
	}

	protected function getHref($str) {
		$str = str_replace('/docs/', '', $str);
		return "href='$str.html";
	}
	protected function getHref2($str) {
		$str = str_replace('/docs/', '', $str);
		return "href=\"$str.html";
	}

	public function syncManualAction() {
		while($c = array_pop(self::$urls)) {
			printf("left:%d, exists:%d\n", count(self::$urls), count(self::$existUrls));
			if (in_array($c, self::$existUrls)) continue;
			$response = Desire_Http::get($this->root . $c);
			if (empty($response->data)) continue;
			preg_match_all("/href='(\/docs\/.*)'/iUs", $response->data, $match);
			isset($match[1]) || $match[1] = array();
			foreach($match[1] as $c1) {
				if (substr($c1, 0, 7) == 'http://') continue;
				$c1 .= '#';
				$c1 = strstr($c1, '#', true);
				if (in_array($c1, self::$existUrls)) continue;
				self::$urls[] = $c1;
			}
			preg_match_all("/href=\"(\/docs\/.*)\"/iUs", $response->data, $match);
			isset($match[1]) || $match[1] = array();
			foreach($match[1] as $c1) {
				if (substr($c1, 0, 7) == 'http://') continue;
				$c1 .= '#';
				$c1 = strstr($c1, '#', true);
				if (in_array($c1, self::$existUrls)) continue;
				self::$urls[] = $c1;
			}
			$content = preg_replace(array(
					'/<div id="support".*<\/div>/iUs',
				),
				array(
					'',
				),
				$response->data
			);

			$content = preg_replace("/href='(\/docs\/[^#']+)/ie", "\$this->getHref('\\1')", $content);
			$content = preg_replace("/href=\"(\/docs\/[^#\"]+)/ie", "\$this->getHref2('\\1')", $content);

			file_put_contents($this->savePath . '/' . str_replace('/docs/', '', $c) . '.html', $content);
			self::$existUrls[] = $c;
			//break;
		}
		$this->preChm();
	}
}