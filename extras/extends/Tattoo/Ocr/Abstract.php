<?php
/**
 * 简单的学习型OCR，用于简单的验证码的破解
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: Abstract.php 833 2014-11-14 09:02:35Z alacner $
 */

abstract class Tattoo_Ocr_Abstract
{
	/**
	 * 非黑即白比较，RGB有2种以上超过阀值的就为黑
	 * @param $a
	 * @param $b
	 * @param $differValue
	 * @return bool
	 */
	private function blackWhite($a, $b, $differValue)
	{
		$bw = 0;

		foreach($a as $k => $v) {
			$bw += (abs($v - $b[$k]) < $differValue) ? 0 : 1;
		}

		return ($bw > 2) ? true :false;
	}

	/**
	 * 处理图片，得到特征点阵数据
	 * @param $filename 图片文件
	 * @param $background 设置背景色，这样在处理的时候固定值,如：#fff
	 * @param $randomNumber 随机采点个数
	 * @param 设置与背景色调的差异值
	 * @return array
	 */
	public function getFeatureData($filename, $background = null, $differValue = 30, $randomNumber = 30)
	{
		$res = imagecreatefromstring(file_get_contents($filename));
		if (!$res) {
			throw new Exception("image create error, filename:" . $filename);
		}

		$size = getimagesize($filename);
		list($width, $height, $type, $attr) = $size;

		//随机一些点，然后根据出现最多的值作为背景值
		if (is_null($background)) {
			$rgbs = array();
			for ($i = 0; $i < $randomNumber; $i++) {
				$w = mt_rand(0, $width);
				$h = mt_rand(0, $height);
				$rgbs[] = (int)imagecolorat($res, $w, $h);
			}
			$rgbs = array_count_values($rgbs); asort($rgbs);
			$background = end(array_keys($rgbs)); unset($rgbs);
		} else {
			$rgb = $this->hex2rgb($background);
			$background = imagecolorallocate($res, $rgb->r, $rgb->g, $rgb->b);
		}
		$background = imagecolorsforindex($res, $background);

		//与背景值的差异，进行是非判断，得到二维的0/1的点阵数据
		$data = array();
		for ($h = 0; $h < $height; ++$h) {
			for ($w = 0; $w < $width; ++$w) {
				$rgb = (int)imagecolorat($res, $w, $h);
				$color = imagecolorsforindex($res, $rgb);
				$data[$h][$w] = $this->blackWhite($background, $color, $differValue);
			}
		}

		$this->filter($size, $data);

		return array($size, $data);
	}

	/**
	 * 排除孤岛点：8点为空的排除
	 * @param $size
	 * @param $data
	 */
	protected function filter($size, &$data)
	{
		list($width, $height, $type, $attr) = $size;

		for ($h = 0; $h < $height; ++$h) {
			for($w = 0; $w < $width; ++$w) {
				$num = 0;
				if ($data[$h][$w] == 1) {
					isset($data[$h-1][$w]) && $num = $num + $data[$h-1][$w];//↑
					isset($data[$h+1][$w]) && $num = $num + $data[$h+1][$w];//↓
					isset($data[$h][$w-1]) && $num = $num + $data[$h][$w-1];//←
					isset($data[$h][$w+1]) && $num = $num + $data[$h][$w+1];//→
					isset($data[$h-1][$w-1]) && $num = $num + $data[$h-1][$w-1];//↖
					isset($data[$h-1][$w+1]) && $num = $num + $data[$h-1][$w+1];//↗
					isset($data[$h+1][$w-1]) && $num = $num + $data[$h+1][$w-1];//↙
					isset($data[$h+1][$w+1]) && $num = $num + $data[$h+1][$w+1];//↘
				}
				if ($num == 0) {
					$data[$h][$w] = 0;
				}
			}
		}
	}

	/**
	 * 获取图片类型
	 * @param $filename
	 * @return bool
	 */
	public function getImageType ($filename)
	{
		if ((list($width, $height, $type, $attr) = getimagesize($filename)) !== false ) {
			return $type;
		}
		return false;
	}

	/**
	 * 获取图片后缀
	 * @param $tempFile
	 * @return string
	 */
	public function getImageExt($tempFile)
	{
		return image_type_to_extension($this->getImageType($tempFile), false);
	}

	/**
	 * 将16进制的颜色转换成10进制的（R，G，B）
	 * @param $color
	 * @return stdClass
	 * @throws Exception
	 */
	public static function hex2rgb($color)
	{
		$color = trim($color);
		if ($color[0] == '#') $color = substr($color, 1);
		if (strlen($color) == 6)
			list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
		elseif (strlen($color) == 3)
			list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
		else {
			throw new Exception('传入的16进制颜色值有误', 500);
		}

		$rgb = new stdClass();
		$rgb->r = hexdec($r);
		$rgb->g = hexdec($g);
		$rgb->b = hexdec($b);

		return $rgb;
	}

	/**
	 * debug
	 * @param array $featureData = $this->getFeatureData($filename)
	 * @param null $filename
	 */
	public function debug(array $featureData, $filename = null, $color = '#fff')
	{
		list($size, $data) = $featureData;
		list($width, $height, $type, $attr) = $size;
		$im = @imagecreatetruecolor($width, $height);

		$rgb = $this->hex2rgb($color);
		$color = imagecolorallocate($im, $rgb->r, $rgb->g, $rgb->b);

		for ($h = 0; $h < $height; ++$h) {
			for($w = 0; $w < $width; ++$w) {
				$data[$h][$w] && imagesetpixel($im, $w , $h , $color);
			}
		}

		if (is_null($filename)) {
			@Header("Content-type: image/png");
			imagepng($im);
		} else {
			imagepng($im, $filename);
		}

		imagedestroy($im);
	}

	/**
	 * debug text
	 * @param array $featureData = $this->getFeatureData($filename)
	 * @param string $m
	 * @param string $n
	 */
	public function debugText(array $featureData, $m = '+', $n = '-')
	{
		list($size, $data) = $featureData;
		list($width, $height, $type, $attr) = $size;
		for ($h = 0; $h < $height; ++$h) {
			for($w = 0; $w < $width; ++$w) {
				echo $data[$h][$w] ? $m : $n;
			}
			echo "\n";
		}
	}

	/**
	 * debug slice text
	 * @param array $featureData = $this->getFeatureData($filename)
	 * @param string $m
	 * @param string $n
	 */
	public function debugSlice(array $featureData, $m = '+', $n = '-')
	{
		$slices = $this->slice($featureData);
		foreach($slices as $k => $v) {
			echo str_replace(array('1', '0'), array($m, $n), $v);
			echo "\n\n";
		}
	}

	/**
	 * 根据学习的模型数据进行相似度比较
	 * @param $data [$numKey => $numString] from $this->slice()
	 * @param $lowPercent 最终比较后值低于这个标准的就返回false
	 * @param $breakPercent 达到这个标准就不继续比较
	 * @return string
	 */
	public function similar(array $featureData, $lowPercent = 70, $breakPercent = 96)
	{
		$sliceStringData = $this->slice($featureData);

		$result = array();

		$models = $this->getModels();
		$sliceNumber = 0;
		$maxPercents = array();

		foreach($sliceStringData as $numKey => $numString) {
			$maxPercents[$sliceNumber] = 0.0;
			$sliceValue = '';

			foreach($models as $slice => $value) {
				similar_text($slice, $numString, $percent);
				//根据相似度，进行剥洋葱式的替换
				if (floatval($percent) > $maxPercents[$sliceNumber]) {
					$maxPercents[$sliceNumber] = $percent;
					$sliceValue = $value;
				}
				//print_r("[$value : $sliceValue : $percent]\n");
				if (intval($percent) > $breakPercent){
					break;
				}
			}

			if ($maxPercents[$sliceNumber] < $lowPercent) {
				return false;
			}
			$result[] = $sliceValue;

			$sliceNumber++;
		}

		return implode('', $result);
	}

	/**
	 * 学习模型
	 * @param $code 正确的值
	 * @param $featureData = $this->getFeatureData($filename)
	 */
	public function study($code, $featureData)
	{
		$code = str_split($code);
		$sliceStringData = $this->slice($featureData);
		$models = $this->getModels();

		$i = 0;
		foreach ($sliceStringData as $slice) {
			$models[$slice] = $code[$i++];
		}

		$this->saveModels($models);
	}

	/**
	 * 获取所有模型数据
	 * @param array $models
	 * @return array
	 */
	abstract protected function getModels();

	/**
	 * 保存所有模型数据
	 * @param array $models
	 * @return boolean
	 */
	abstract protected function saveModels(array $models);

	/**
	 * 切片方法，用于切出需要OCR的图片块
	 *  @param array $featureData = $this->getFeatureData($filename)
	 * @return array [$numKey => $numString]
	 */
	abstract public function slice(array $featureData);
}