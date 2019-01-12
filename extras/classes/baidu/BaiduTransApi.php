<?php

class BaiduTransApi {
    protected $url = "http://fanyi.baidu.com/v2transapi";

	protected $apiUrl = "http://openapi.baidu.com/public/2.0/bmt/translate";

	protected $langJsonStr = "{'zh': '中文','jp': '日语','jpka': '日语假名','th': '泰语','fra': '法语','en': '英语','spa': '西班牙语','kor': '韩语','tr': '土耳其语','vi': '越南语','ms': '马来语','de': '德语','ru': '俄语','ir': '伊朗语','ara': '阿拉伯语','est': '爱沙尼亚语','be': '白俄罗斯语','bul': '保加利亚语','hi': '印地语','is': '冰岛语','pl': '波兰语','fa': '波斯语','dan': '丹麦语','tl': '菲律宾语','fin': '芬兰语','nl': '荷兰语','ca': '加泰罗尼亚语','cs': '捷克语','hr': '克罗地亚语','lv': '拉脱维亚语','lt': '立陶宛语','rom': '罗马尼亚语','af': '南非语','no': '挪威语','pt_BR': '巴西语','pt': '葡萄牙语','swe': '瑞典语','sr': '塞尔维亚语','eo': '世界语','sk': '斯洛伐克语','slo': '斯洛文尼亚语','sw': '斯瓦希里语','uk': '乌克兰语','iw': '希伯来语','el': '希腊语','hu': '匈牙利语','hy': '亚美尼亚语','it': '意大利语','id': '印尼语','sq': '阿尔巴尼亚语','am': '阿姆哈拉语','as': '阿萨姆语','az': '阿塞拜疆语','eu': '巴斯克语','bn': '孟加拉语','bs': '波斯尼亚语','gl': '加利西亚语','ka': '格鲁吉亚语','gu': '古吉拉特语','ha': '豪萨语','ig': '伊博语','iu': '因纽特语','ga': '爱尔兰语','zu': '祖鲁语','kn': '卡纳达语','kk': '哈萨克语','ky': '吉尔吉斯语','lb': '卢森堡语','mk': '马其顿语','mt': '马耳他语','mi': '毛利语','mr': '马拉提语','ne': '尼泊尔语','or': '奥利亚语','pa': '旁遮普语','qu': '凯楚亚语','tn': '塞茨瓦纳语','si': '僧加罗语','ta': '泰米尔语','tt': '塔塔尔语','te': '泰卢固语','ur': '乌尔都语','uz': '乌兹别克语','cy': '威尔士语','yo': '约鲁巴语','yue': '粤语','wyw': '文言文','cht': '中文繁体'}";

    protected $header = array(
		"Referer" => "http://fanyi.baidu.com/",
		"X-Requested-With" => "XMLHttpRequest",
		"Accept" => "*/*",
		"Origin" => "http://fanyi.baidu.com",
		'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36”)',
	);
	protected $from = 'zh';
	protected $to = 'en';

	/**
	 * @param string $to
	 * @param string $from
	 */
	public function __construct($to = 'en', $from = 'zh')
	{
		$this->from = $from;
		$this->to = $to;
	}

	/**
	 * 语言列表
	 * @return mixed
	 */
	public function getLangList()
	{
		return json_decode($this->langJsonStr, true);
	}

	/**
	 * @param $query
	 * @return mixed
	 */
	public function _trans($query)
	{
		$post = array(
			'from' => $this->from,
			'to' => $this->to,
			'query' => Desire_Security_Filter::str($query),
			'transtype' => 'realtime',
		);

		$response = Desire_Http::post($this->url, array($post), $this->header);
		return json_decode($response->data, true);
	}

	/**
	 * @param $query
	 * @return mixed
	 */
	public function _transApi($query)
	{
		$post = array(
			'from' => $this->from,
			'to' => $this->to,
			'q' => Desire_Security_Filter::str($query),
			'client_id' => 'ecX0dMot3qjeUOWwDNO42bGy',
		);

		$response = Desire_Http::post($this->apiUrl, array($post), $this->header);
		return json_decode($response->data, true);
	}

	/**
	 * @param $str
	 * @return string
	 */
	public function trans($str)
	{
		if (empty($str)) return $str;

		$res = $this->_trans($str);
		if (!isset($res['trans_result'])) {
			$res = $this->_transApi($str);
		}
		if (!isset($res['trans_result'])) {
			$res = $this->_trans($str);
		}

		if (!isset($res['trans_result'])) return "";

		$dsts = array();
		foreach($res['trans_result']['data'] as $d) {
			$dsts[] = $d['dst'];
		}
		return implode("\n", $dsts);
	}

	/**
	 * @param $arr
	 * @param string $splitStr
	 * @return array
	 */
	public function transArray($arr, $splitStr = "\n")
	{
		if (empty($arr)) return array();
		$newStrArr = array_values(array_unique($arr));
		rsort($newStrArr);

		usort($newStrArr, function($a, $b) {
			return strlen($a) < strlen($b);
		});

		$transed = explode($splitStr, $this->trans(implode($splitStr, $newStrArr)));

		$transed = array_combine($newStrArr, $transed);

		$result = array();
		foreach($arr as $k => $v) {
			$result[$k] = in_array($k, $transed) ? $transed[$k] : $v;
		}

		return $result;
	}

	/**
	 * @param $str
	 * @return string
	 */
	public function streamTrans($str, $splitStr = "\n")
	{
		$chars = Tattoo_String::getChars($str);

		$newStrArr = array();
		$tmpChars = array();
		foreach($chars as $c) {
			if (ord($c) > 127) {
				$tmpChars[] = $c;
			} else {
				if ($tmpChars) {
					$newStrArr[] = implode("", $tmpChars);
					$tmpChars = array();
				}
			}
		}

		if ($tmpChars) {
			$newStrArr[] = implode("", $tmpChars);
		}

		$newStrArr = array_values(array_unique($newStrArr));
		rsort($newStrArr);

		usort($newStrArr, function($a, $b) {
			return strlen($a) < strlen($b);
		});

		$transed = explode($splitStr, $this->trans(implode($splitStr, $newStrArr)));
		//print_r(array($newStrArr, $transed));

		return str_replace($newStrArr, $transed, $str);
	}
}