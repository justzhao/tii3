<?php
/**
 * 系统文件的扩展类
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Filesystem.php 1608 2015-06-16 04:11:56Z alacner $
 */

class Tattoo_Filesystem
{
	/**
	 * 根据内容返回数据类型
	 */
	public static function getFiletype(&$data)
	{
		switch (substr($data ,0, 8)) {
			case "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" : return array('png');
		}
		
		switch (substr($data ,0, 4)) {
			case "GIF8" : return array('gif');
			case 'CWS':
			case 'FWS': return array('swf');
		}
		
		switch (substr($data ,0, 3)) {
			case "\xFF\xD8\xFF" : return array('jpg','jpeg');
			case 'PK\x03\x04': return array('zip');
			case 'Rar!': return array('rar');
			case '\x25PDF': return array('pdf');
			case 'ITSF': return array('chm');
			case '\x2ERMF': return array('rm');
			case '\xD0\xCF\x11\xE0': return array('doc','xls','ppt');
		}
		
		switch (substr($data ,0, 2)) {
			case "BM" : return array('bmp');
			case "MZ" : return array('exe');
		}
		return array();
	}

	/**
	 * Returns a human readable memory size
	 *
	 * @param   int    $size
	 * @param   string $format   The format to display (printf format)
	 * @param   int    $round
	 * @return  string
	 */
	public static function convertBytesToHumanReadable($size, $format = null, $round = 3)
	{
		$mod = 1024;

		if (is_null($format)) {
			$format = '%.2f%s';
		}

		$units = explode(' ','B Kb Mb Gb Tb PB EB ZB YB');

		for ($i = 0; $size > $mod; $i++) {
			$size /= $mod;
		}

		if (0 === $i) {
			$format = preg_replace('/(%.[\d]+f)/', '%d', $format);
		}

		return sprintf($format, round($size, $round), $units[$i]);
	}


	/**
	 * Convert a size from human readable format (with a unit like K, M, G for Kilobytes, Megabytes, etc.)
	 * to a size in bytes.
	 * @param string $val
	 * @return string
	 */
	public static function convertHumanReadableToBytes($val)
	{
		$mod = 1024;
		$val = trim($val);
		$types = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
		$pow = array_search(strtoupper(substr($val, -2)), $types);
		$pow || $pow = 0;
		$val *= pow($mod, $pow);
		return $val;
	}
}