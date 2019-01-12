<?php
/**
 * XXTEA encryption arithmetic library.
 * Copyright (C) 2006 Ma Bingyao <andot@ujn.edu.cn>
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Xxtea.php 488 2014-10-14 10:03:34Z alacner $
 */

final class Tattoo_Security_Common_Xxtea
{
	/**
	 * 加密算法实现接口
	 *
	 * @param string $string
	 * @param string $key
	 * @return string 加密后的结果
	 */
	public static function encrypt($str, $key)
	{
		if ($str == '') return '';
		if (!$key || !is_string($key)) {
			throw new Desire_Exception('security key is required.');
		}
		$v = self::str2long($str, true);
		$k = self::str2long($key, false);
		if (count($k) < 4) {
			for ($i = count($k); $i < 4; $i++) {
				$k[$i] = 0;
			}
		}
		$n = count($v) - 1;

		$z = $v[$n];
		$y = $v[0];
		$delta = 0x9E3779B9;
		$q = floor(6 + 52 / ($n + 1));
		$sum = 0;
		while (0 < $q--) {
			$sum = self::int32($sum + $delta);
			$e = $sum >> 2 & 3;
			for ($p = 0; $p < $n; $p++) {
				$y = $v[$p + 1];
				$mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(
					($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
				$z = $v[$p] = self::int32($v[$p] + $mx);
			}
			$y = $v[0];
			$mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(
				($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
			$z = $v[$n] = self::int32($v[$n] + $mx);
		}
		return self::long2str($v, false);
	}

	/**
	 * 解密算法实现
	 *
	 * @param string $string
	 * @param string $key
	 * @return string 解密后的结果
	 */
	public static function decrypt($str, $key)
	{
		if ($str == '') return '';
		if (!$key || !is_string($key)) {
			throw new Desire_Exception('security key is required.');
		}
		$v = self::str2long($str, false);
		$k = self::str2long($key, false);
		if (count($k) < 4) {
			for ($i = count($k); $i < 4; $i++) {
				$k[$i] = 0;
			}
		}
		$n = count($v) - 1;

		$z = $v[$n];
		$y = $v[0];
		$delta = 0x9E3779B9;
		$q = floor(6 + 52 / ($n + 1));
		$sum = self::int32($q * $delta);
		while ($sum != 0) {
			$e = $sum >> 2 & 3;
			for ($p = $n; $p > 0; $p--) {
				$z = $v[$p - 1];
				$mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(
					($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
				$y = $v[$p] = self::int32($v[$p] - $mx);
			}
			$z = $v[$n];
			$mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(
				($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
			$y = $v[0] = self::int32($v[0] - $mx);
			$sum = self::int32($sum - $delta);
		}
		return self::long2str($v, true);
	}

	/**
	 * 长整型转换为字符串
	 *
	 * @param long $v
	 * @param boolean $w
	 * @return string
	 */
	protected static function long2str($v, $w)
	{
		$len = count($v);
		$s = array();
		for ($i = 0; $i < $len; $i++) {
			$s[$i] = pack("V", $v[$i]);
		}
		return $w ? substr(join('', $s), 0, $v[$len - 1]) : join('', $s);
	}

	/**
	 * 字符串转化为长整型
	 *
	 * @param string $s
	 * @param boolean $w
	 * @return Ambigous <multitype:, number>
	 */
	protected static function str2long($s, $w)
	{
		$v = unpack("V*", $s . str_repeat("\0", (4 - strlen($s) % 4) & 3));
		$v = array_values($v);
		if ($w) $v[count($v)] = strlen($s);
		return $v;
	}

	/**
	 * @param int $n
	 * @return number
	 */
	protected static function int32($n)
	{
		while ($n >= 2147483648) $n -= 4294967296;
		while ($n <= -2147483649) $n += 4294967296;
		return (int) $n;
	}
}