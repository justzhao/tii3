<?php

class Task_TopController extends Desire_Application_Controller_Abstract
{
	public function indexAction() {
		$this->mainPage();
	}

	protected static $urls = array();
	protected static $existUrls = array();
	protected $chmPath = 'D:/top';
	protected $sourcePath;
	protected $savePath;
	protected $apidoc = 'http://api.taobao.com/apidoc/inner/content.htm?path=';
	protected $entryPage = 'http://api.taobao.com/apidoc/inner/index.htm';

	protected $headerHtml = '';
	protected $footerHtml = '';

	public function init() {
		$this->sourcePath = $this->chmPath . '/source';
		$this->savePath = $this->chmPath . '/chm';

		is_dir($this->savePath) || mkdir($this->savePath, 0777, true);

		$this->headerHtml = file_get_contents($this->sourcePath . '/header.html');
		$this->footerHtml = file_get_contents($this->sourcePath . '/footer.html');

		$this->preSync();
		$this->mainPage();
	}

	protected function preSync() {
		$response = Desire_Http::get($this->entryPage);
		preg_match_all('/href="#(categoryId:\d+)"/i', $response->data, $match);
		if (!isset($match[1])) return false;
		foreach($match[1] as $url) {
			self::$urls[] = $url;
		}
		return true;
	}

	protected function mainPage() {
		$url = 'http://open.taobao.com/doc/detail.htm?id=134#Field_List';
		$response = Desire_Http::get($url);
		if (array_key_exists('Location', $response->headers)) {
			$response = Desire_Http::get($response->headers['Location']);
		}
		//print_r($response);
		preg_match_all('/<p><a name="([^"]+)".*<h3><span>(.*)<\/span>.*<dd>(.*)<\/dd>/iUs', $response->data, $m);
		unset($m[0]);
		$paramTypes = $m;
		//print_r($paramTypes);exit;

		$mainPage = 'http://my.open.taobao.com/apidoc/main.htm';
		$response = Desire_Http::get($mainPage);
		if (array_key_exists('Location', $response->headers)) {
			$response = Desire_Http::get($response->headers['Location']);
		}

		preg_match_all('/<li class="api-list-item.*<a href=".*\?cat_id=(\d+)&[^>]+>(.*)<\/a>.*<p class="api-list-intro">(.*)<\/p>/iUs', $response->data, $m);
		unset($m[0]);
		$allApis = $m;
		//print_r($allApis);exit;

		ob_start();
		ob_implicit_flush(true);
		include_once $this->sourcePath . '/main.phtml';
		$content = ob_get_clean();
		//print_r($content);
		file_put_contents($this->savePath . '/main.html', $this->headerHtml . $content . $this->footerHtml);

		return true;
	}

	protected function getHref($str) {
		return 'href="' . str_replace(':', '_', $str) . '.html"';
	}

	public function syncWikiAction() {
		while($c = array_pop(self::$urls)) {
			printf("left:%d, exists:%d, url:%s\n", count(self::$urls), count(self::$existUrls), $this->apidoc . $c);
			if (in_array($c, self::$existUrls)) continue;
			try {
				$response = Desire_Http::get($this->apidoc . $c);
			} catch(Exception $e) {
				try {
					$response = Desire_Http::get($this->apidoc . $c);
				} catch(Exception $e) {
					printf("Exeption ...");
					continue;
				}
			}
			if (empty($response->data)) continue;
			preg_match_all('/href="#([^"]+)"/i', $response->data, $match);
			isset($match[1]) || $match[1] = array();
			foreach($match[1] as $c1) {
				if (in_array($c1, self::$existUrls)) continue;
				self::$urls[] = $c1;
			}

			$content = preg_replace('/href="#([^"]+)"/ie', "\$this->getHref('\\1')", $response->data);

			$content = preg_replace(array(
					'/href="\/apitools/i',
					'/href="\/apidoc/i',
					'/http:\/\/open.taobao.com\/dev\/index.php\/%E5%8F%82%E6%95%B0%E7%B1%BB%E5%9E%8B%E8%AF%B4%E6%98%8E/i',
					'/(<a href="main.html#.*" )target="_blank">/i',
				),
				array(
					'href="http://my.open.taobao.com/apitools',
					'href="http://my.open.taobao.com/apidoc',
					'main.html',
					'\\1>',
				),
				$content
			);

			file_put_contents($this->savePath . '/' . str_replace(':', '_', $c) . '.html', $this->headerHtml . $content . $this->footerHtml);
			self::$existUrls[] = $c;
			//break;
		}
		$this->preChm();
	}


	protected function preChm() {
		$files = Desire_Filesystem::getFiles($this->savePath . '/', array('html'));
		$categoryFiles = array();
		foreach ($files as $file) {
			preg_match('/categoryId_(\d+).html/', $file, $m);
			if (isset($m[1])) {
				$categoryFiles[] = $file;
			}
		}
		natsort($categoryFiles);

		$indexes = $hhcs = $files = array();

		$hhcs[] = array(
			'name' => 'TOP API 文档',
			'local' => 'main.html',
		);

		foreach ($categoryFiles as $categoryFile) {
			$hhc = array();
			$content = file_get_contents($categoryFile);
			preg_match('/<h1>(.*)<\/h1>/i', $content, $m);
			if (!isset($m[1])) continue;
			$hhc['name'] = $m[1];
			$hhc['local'] = basename($categoryFile);
			$files[] = $hhc['local'];
			$indexes[] = $hhc;
			unset($m);

			foreach (explode('class="section"', $content) as $c) {
				$sub = array();
				preg_match('/<h2>([^<h2]+)<\/h2>.*<table class="para_table">(.*)<\/table>/iUs', $c, $m);
				if (!isset($m[2]) || empty($m[2])) continue;
				$sub['name'] = $m[1];
				$sub['local'] = basename($categoryFile);
				preg_match_all('/<a href="([^"]+)">(.*)<\/a>.*<td>(.*)<\/td>/iUs', $m[2], $m1);
				if (!isset($m1[1]) || empty($m1[1])) continue;
				for ($i = 0, $j = count($m1[3]); $i < $j; $i++) {
					$sub['sub'][] = array(
						'name' => $m1[2][$i] . '(' . $m1[3][$i] . ')',
						'local' => $m1[1][$i],
					);

					$indexes[] = array(
						'name' => $m1[3][$i],
						'local' => $m1[1][$i],
					);
					$indexes[] = array(
						'name' => $m1[2][$i],
						'local' => $m1[1][$i],
					);

					$files[] = $m1[1][$i];
				}
				$hhc['sub'][] = $sub;
			}
			//print_r($hhc);
			$hhcs[] = $hhc;
			//break;
		}

		ob_start();
		include_once $this->sourcePath . '/hhc.phtml';
		$content = ob_get_clean();
		$content = iconv('utf-8', 'gb2312//TRANSLIT', $content);
		file_put_contents($this->savePath . '/contents.hhc', $content);

		ob_start();
		include_once $this->sourcePath . '/hhk.phtml';
		$content = ob_get_clean();
		$content = iconv('utf-8', 'gb2312//TRANSLIT', $content);
		file_put_contents($this->savePath . '/index.hhk', $content);

		ob_start();
		$date = Desire_Time::format('Ymd');

		$fs = Desire_Filesystem::getFiles($this->savePath . '/apidoc', array('js', 'png', 'gif', 'css', 'swf'), false);
		foreach ($fs  as $f) {
			$files[] = 'apidoc/' . $f;
		}

		$inner = stripos($this->apidoc, 'inner') !== false;
		include_once $this->sourcePath . '/hhp.phtml';
		$content = ob_get_clean();
		$content = iconv('utf-8', 'gb2312//TRANSLIT', $content);
		file_put_contents($this->savePath . '/chm_hhp.hhp', $content);
	}
}