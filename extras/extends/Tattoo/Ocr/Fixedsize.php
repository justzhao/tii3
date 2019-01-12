<?php
/**
 * 根据固定间隔进行分块
 */
class Tattoo_Ocr_Fixedsize extends Tattoo_Ocr_Simple
{
	private $left = 5;
	private $length = 15;
	private $number = 4;

	public function __construct($filename, $left = 5, $length = 15, $number = 4)
	{
		parent::__construct($filename);
		$this->left = $left;
		$this->length = $length;
		$this->number = $number;
	}

	/**
	 * 过滤射线3次的点
	 * @param $size
	 * @param $data
	 */
	private function filter3($size, &$data)
	{
		list($width, $height, $type, $attr) = $size;

		//排除孤岛点：8点为空的排除
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

					isset($data[$h-2][$w]) && $num = $num + $data[$h-2][$w];//↑
					isset($data[$h+2][$w]) && $num = $num + $data[$h+2][$w];//↓
					isset($data[$h][$w-2]) && $num = $num + $data[$h][$w-2];//←
					isset($data[$h][$w+2]) && $num = $num + $data[$h][$w+2];//→
					isset($data[$h-2][$w-2]) && $num = $num + $data[$h-2][$w-2];//↖
					isset($data[$h-2][$w+2]) && $num = $num + $data[$h-2][$w+2];//↗
					isset($data[$h+2][$w-2]) && $num = $num + $data[$h+2][$w-2];//↙
					isset($data[$h+2][$w+2]) && $num = $num + $data[$h+2][$w+2];//↘

					isset($data[$h-3][$w]) && $num = $num + $data[$h-3][$w];//↑
					isset($data[$h+3][$w]) && $num = $num + $data[$h+3][$w];//↓
					isset($data[$h][$w-3]) && $num = $num + $data[$h][$w-3];//←
					isset($data[$h][$w+3]) && $num = $num + $data[$h][$w+3];//→
					isset($data[$h-3][$w-3]) && $num = $num + $data[$h-3][$w-3];//↖
					isset($data[$h-3][$w+3]) && $num = $num + $data[$h-3][$w+3];//↗
					isset($data[$h+3][$w-3]) && $num = $num + $data[$h+3][$w-3];//↙
					isset($data[$h+3][$w+3]) && $num = $num + $data[$h+3][$w+3];//↘
				}
				//print_r("[$num]");
				if ($num < 10) {
					$data[$h][$w] = 0;
				}
			}
		}
	}

	protected function filter($size, &$data)
	{
		parent::filter($size, $data);

		$this->filter3($size, $data);
		$this->filter3($size, $data);
	}

	/**
	 * 切片方法，用于切出需要OCR的图片块
	 *  @param array $featureData = $this->getFeatureData($filename)
	 * @return array [$numKey => $numString]
	 */
	public function slice(array $featureData)
	{
		list($size, $data) = $featureData;

		list($width, $height, $type, $attr) = $size;
		$sliceData = array();
		$sliceNumber = 0;

		for ($w = $this->left+1; $w < $width; ++$w) {
			$slices = array();
			for($h = 0; $h < $height; ++$h) {
				$slices[$h] = $data[$h][$w];
			}

			if (($w - $this->left) % $this->length == 0) $sliceNumber++;
			if ($this->number == $sliceNumber) break;
			$sliceData[$sliceNumber][] = $slices;
		}


		$sliceStringData = array();
		foreach ($sliceData as $sliceNumber => $slices) {
			if (!isset($slices[0])) continue;
			//print_r($slices);
			$hc = count($slices);
			$vc = count($slices[0]);

			//From top to bottom
			for($v = 0; $v < $vc; ++$v) {
				$nl = 0;
				for($h = 0; $h < $hc; ++$h) {
					$nl += $slices[$h][$v];
				}
				if (intval($nl) === 0) {
					for($h = 0; $h < $hc; ++$h) {
						unset($slices[$h][$v]);
					}
				} else {
					break;
				}
			}
			//From bottom to top
			for($v = $vc-1; $v >= 0; --$v) {
				$nl = 0;
				for($h = 0; $h < $hc; ++$h) {
					$nl += $slices[$h][$v];
				}
				if (intval($nl) === 0) {
					for($h = 0; $h < $hc; ++$h) {
						unset($slices[$h][$v]);
					}
				} else {
					break;
				}
			}

			//From left to right
			for($h = 0; $h < $hc; ++$h) {
				$nl = 0;
				for($v = 0; $v < $vc; ++$v) {
					$nl += $slices[$h][$v];
				}
				if (intval($nl) === 0) {
					for($v = 0; $v < $vc; ++$v) {
						unset($slices[$h][$v]);
					}
				} else {
					break;
				}
			}

			//From right to left
			for($h = $hc-1; $h >= 0; --$h) {
				$nl = 0;
				for($v = 0; $v < $vc; ++$v) {
					$nl += $slices[$h][$v];
				}
				if (intval($nl) === 0) {
					for($v = 0; $v < $vc; ++$v) {
						unset($slices[$h][$v]);
					}
				} else {
					break;
				}
			}

			//to implode
			$_slices = array();
			foreach($slices as $s) {
				$_slices[] = implode("", $s);
			}
			$sliceStringData[$sliceNumber] = implode("\n", $_slices);
		}

		return $sliceStringData;
	}
} 