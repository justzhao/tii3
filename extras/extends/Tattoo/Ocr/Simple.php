<?php
/**
 * 简单的根据通栏空的数据进行分离
 */
class Tattoo_Ocr_Simple extends Tattoo_Ocr_Abstract
{
	private $filename;

	public function __construct($filename)
	{
		$this->filename = $filename;
	}

	/**
	 * 获取所有模型数据
	 * @param array $models
	 * @return array
	 */
	protected function getModels()
	{
		return (array) unserialize(file_get_contents($this->filename));
	}

	/**
	 * 保存所有模型数据
	 * @param array $models
	 * @return boolean
	 */
	protected function saveModels(array $models)
	{
		return file_put_contents($this->filename, serialize($models));
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

		for ($w = 0; $w < $width; ++$w) {
			$slices = array();
			$nl = 0;
			for($h = 0; $h < $height; ++$h) {
				$slices[$h] = $data[$h][$w];
				$nl += $data[$h][$w];
			}

			if (intval($nl) === 0) {
				$sliceNumber++;
			} else {
				$sliceData[$sliceNumber][] = $slices;
			}
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