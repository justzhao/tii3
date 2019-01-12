<?php
/**
 * 字符串类
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: String.php 8881 2017-10-16 13:35:52Z alacner $
 */

final class Tattoo_String
{
	/**
	 * @see http://w3.org/International/questions/qa-forms-utf-8.html
	 * @param $str
	 * @return int
	 */
	public static function isUtf8($str)
	{
		return preg_match('%^(?:
			[\x09\x0A\x0D\x20-\x7E] # ASCII
			| [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte
			| \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
			| \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
			| \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15
			| \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
			)*$%xs', $str);
	}

	/**
	 * split UTF-8
	 * @param $str
	 * @param $charset
	 * @return mixed
	 */
	public static function getChars($str, $charset="utf-8")
	{
		static $patterns;
		$patterns || $patterns = [
			//'utf-8' => "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/",
			'utf-8' => "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf]{2}|\xf0[\x90-\xbf][\x80-\xbf]{2}|[\xf1-\xf7][\x80-\xbf]{3}/",
			'gb2312' => "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/",
			'gbk' => "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/",
			'big5' => "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/",
		];

		preg_match_all($patterns[$charset], $str, $info);
		return $info[0];
	}

    /**
     * Split a string into smaller chunks
     *
     * @param $body
     * @param int $chunklen
     * @param string $end
     * @return string
     */
    public static function chunk($body, $chunklen = 76, $end = "\r\n") {
        $out = [];
        foreach(array_chunk(self::getChars($body), $chunklen) as $chunk) {
            $out[] = implode('', $chunk);
        }
        return implode($end, $out);
    }

    /**
	 * @param $str
	 * @param string $charset
	 * @return int
	 */
	public static function strlen($str, $charset="utf-8")
	{
		return count(self::getChars($str, $charset));
	}

	/**
	 * UTF-8 Cutting
	 *
	 * @param $str 需要cutting的字符串
	 * @param $start 开始处
	 * @param $end 结束处
	 * @param $len 长度
	 * @return array|string
	 */
	public static function msubstr($str, $start, $end, $len = 0)
	{
		$info = self::getChars($str);
		$lens = sizeof($info);
		if ($len == 1) return array(join("", array_slice($info, $start, $end)), $lens);
		else return join("", array_slice($info, $start, $end));
	}

	/**
	 * 截取字符
	 *
	 * @param string $string
	 * @param int $length
	 * @param string $charset
	 * @param string $dot
	 * @return string
	 */
	public static function cutstr($string, $length, $charset = 'utf-8', $dot = '')
	{
		if (strlen($string) <= $length) {
			return $string;
		}
		$strcut = '';
		if ($charset == 'utf-8') {
			$strcut = self::msubstr($string, 0, $length - strlen($dot) - 1); //UTF-8 Cutting
		} else {
			for ($i = 0, $j = $length - strlen($dot) - 1; $i < $j; $i++) {
				$strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
			}
		}
		return $strcut.$dot;
	}


	/**
	 * php 版本的escape函数
	 *
	 * @param string $str 输入的字符串
	 * @param string $encode 输入编码
	 * @param bool $high_lower 可能会遇到机器码高低位引起的乱码，需要进行更改。
	 * @return string
	 */
	public static function escape($str, $encode = 'UTF-8', $high_lower = false)
	{
		$str = iconv($encode, "GBK", $str);
		$reString = '';
		preg_match_all("/[\x80-\xff].|[\x01-\x7f]+/",$str,$newstr);
		$ar = $newstr[0];
		foreach($ar as $k => $v) {
			if (ord($ar[$k]) >= 127) {
				$tmpString=bin2hex(iconv("GBK","UCS-2",$v));
				$tmpString = $high_lower ? $tmpString : substr($tmpString,2,2).substr($tmpString,0,2);
				$reString.="%u".$tmpString;
			} else {
				$reString.= rawurlencode($v);
			}
		}
		return $reString;
	}


	/**
	 * php 的escape还原函数
	 *
	 * @param string $str 输入的字符串
	 * @param string $encode 返回编码
	 * @param bool $high_lower 可能会遇到机器码高低位引起的乱码，需要进行更改。
	 * @return string
	 */
	public static function unescape($str, $encode = 'UTF-8', $high_lower = false)
	{
		$str = rawurldecode($str);
		preg_match_all("/%u.{4}|&#x.{4};|&#d+;|.+/U",$str,$r);
		$ar = $r[0];
		foreach($ar as $k=>$v){
			if (substr($v, 0, 2) == "%u") {
				$tmpString = $high_lower ? substr($v,-4) : substr($v,-2,2).substr($v,-4,2);
				$ar[$k] = iconv("UCS-2", $encode, pack("H4", $tmpString));
			}
			elseif(substr($v,0,3) == "&#x")
				$ar[$k] = iconv("UCS-2", $encode, pack("H4",substr($v,3,-1)));
			elseif(substr($v,0,2) == "&#")
				$ar[$k] = iconv("UCS-2", $encode, pack("n",substr($v,2,-1)));
		}
		return join("",$ar);
	}


	/**
	 * 把中文等转换成 &#方式的html串
	 *
	 * @param string $str 输入的字符串
	 * @param boolean $isemail 是否邮箱
	 * @param string $encode 当前编码
	 * @param boolean $high_lower 可能会遇到机器码高低位引起的乱码，需要进行更改。
	 * @return string
	 */
	public static function html_view_code($str, $isemail = false, $encode = 'UTF-8', $high_lower = false)
	{
		$output = '';
		$str = iconv($encode, "UTF-16", $str);
		for ($i = 0, $j = strlen($str); $i < $j; $i++,$i++) {
			$code = $high_lower ? ord($str{$i}) * 256 + ord($str{$i + 1}) : ord($str{$i + 1}) * 256 + ord($str{$i});
			if ($code < 128 and !$isemail) {
				$output .= chr($code);
			} else if ($code != 65279) {
				$output .= "&#".$code.";";
			}
		}
		return $output;
	}

	/**
	 * fixed 'false' to false
	 * @param $str
	 * @return bool
	 */
	public static function toBool($str)
	{
		if (is_bool($str)) return $str;
		return in_array($str, array('false')) ? false : $str;
	}

	/**
	 * @param $xml
	 * @return mixed
	 */
	public static function xml2array($xml)
	{
		return json_decode(json_encode(simplexml_load_string($xml)), true);
	}

	/**
	 * @param $array
	 * @param $tabs
	 * @return string
	 */
	public static function array2xml(array $array, $tabs = -1, $nkey = "")
	{
		$return = "";

		if ($nkey) $itabs = $tabs+1;

		foreach ($array as $key => $value) {

			if (is_numeric($key)) {
				if ($nkey) $return .= str_pad('',$tabs,"\t") . '<' . $nkey . '>';
				if (is_array($value)) {
					$return .= "\n" . self::array2xml($value, $itabs, "") . "\n" . str_pad('', $tabs,"\t");
				} else {
					$return .= htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');
				}
				if ($nkey) $return .= '</' . $nkey . '>' . "\n";
				continue;
			}

			$key = htmlspecialchars($key, ENT_NOQUOTES, 'UTF-8');
			$key = str_replace(array('?', '!', ' ', '/'), array('&#63;', '&#33;', '&nbsp;', '&#47;'), $key);
			if (!$nkey) $return .= str_pad('',$tabs,"\t") . '<' . $key . '>';
			if (is_array($value)) {
				$return .= "\n" . self::array2xml($value, $itabs, $key) . "\n" . str_pad('', $tabs,"\t");
			} else {
				$return .= htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');
			}
			if (!$nkey) $return .= '</' . $key . '>' . "\n";
		}

		return trim($return, "\n");
	}
}