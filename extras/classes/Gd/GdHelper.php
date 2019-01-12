<?php
/**
 * 图片操作帮助类
 * @author Zhihua Zhang <alacner@gmail.com> 2011-04-21
 * @version $Id: GdHelper.php 478 2012-04-03 07:17:21Z yametei $
 */
require_once dirname(__FILE__) . '/Gd.php';

class Gd_Helper
{
	protected $width = 0; //图像宽度的像素值
	protected $height = 0; //图像高度的像素值
	protected $square = 0; //图像面积
	
	protected $handle;
	
	public function __construct($handle, $isFromString = false)
	{
		if (is_resource($handle)) {
			$this->handle = $handle;
			$this->width = ImageSX($handle);
			$this->height = ImageSY($handle);
		} else {
			$im = $isFromString? Gd::loadImageFromString($handle) : Gd::loadImage($handle);
			list($this->handle, $this->width, $this->height) = array($im->handle, $im->width, $im->height);
		}
		
		$this->square = $this->width * $this->height;
	}

	public function getHandle()
	{
		return $this->handle;
	}

	public function resizeImage($scale = 1, $newwidth = 0, $newheight = 0)
	{
		$new_w = $this->width;
		$new_h = $this->height;
		$aspect_ratio = (int) $new_h / $new_w;
		if ($scale) $new_w = $new_w * $scale;
		if ($newwidth > 0) $new_w = $newwidth;
		if ($newheight > 0) {
			$new_h = $newheight;
			$new_w = (int) $new_h / $aspect_ratio;
		} else {
			$new_h = abs($new_w * $aspect_ratio);
		}
		
		return Gd::copyCreateHandle($this->handle, 0, 0, 0, 0, $new_w, $new_h, $this->width, $this->height);
	}
	
	public function mirrorImageX()
	{
		$imgnew = Gd::createImage($this->width, $this->height);
		for ($y = 0; $y < $this->height; $y++) {
			imagecopy($imgnew, $this->handle, 0, $this->height - $y - 1, 0, $y, $this->width, 1);
		}
		return $imgnew;
	}
	
	public function mirrorImageY()
	{
		$imgnew = Gd::createImage($this->width, $this->height);
		for ($x = 0; $x < $this->width; $x++) {
			imagecopy($imgnew, $this->handle, $this->width - $x - 1, 0, $x, 0, 1, $this->height);
		}
		return $imgnew;
	}
	
	/**
	 * 任意角度旋转
	 * 注意，必须为浮点数
	 */
	public function angle($circumrotate = '90.0')
	{
		$imgnew = Gd::createImage($this->width, $this->height);
		imagecopyresampled($imgnew, $this->handle,0,0,0,0,$this->width,$this->height,$this->width,$this->height);
		$white = imagecolorallocate($imgnew,255,255,255);
		$imgnew2 = imagerotate ($imgnew, $circumrotate, $white);
		imagedestroy($imgnew);
		return $imgnew2;
	}
	
	/**
	 * 文字水印
	 */
	public function text($text, $pos = 0, $font = array())
	{
		if (!function_exists('imagettfbbox')) {
			throw new Gd_Exception('imagettfbbox 函数不能使用，请安装 FreeType 库');
		}
		if (!function_exists('mb_convert_encoding')) {
			throw new Gd_Exception('mb_convert_encoding 函数不能使用，请安装 Multibyte String 库');
		}
		
		$defaultFont = array('size' => 12, 'angle' => 0, 'color' => '#000000', 'fontfile' => dirname(__FILE__) . '/fonts/simkai.ttf', 'charset' => 'gb2312');
		$font = array_merge($defaultFont, $font);
		
		if (!is_file($font['fontfile'])) {
			throw new Gd_Exception('TrueType 字体文件的文件名（可以是 URL）不存在。');
		}
		$rgb = Gd::hex2rgb($font['color']);
		
		(str_replace('-', '', $font['charset']) !== 'utf8') && $text = mb_convert_encoding($text,'utf-8', $font['charset']);
		$tmp = imagettfbbox($font['size'], 0, $font['fontfile'], $text); // 取得使用 TrueType 字体的文本的范围
		$textWidth = intval($tmp[2] - $tmp[6]);
		$textHeight = intval($tmp[3] - $tmp[7]);
		
		if (is_array($pos)) {
			$pos['x'] || $pos['x'] = 0;
			$pos['y'] || $pos['y'] = $textHeight;
		} else {
			$intPos = intval($pos);
			$pos = array('x' => 0, 'y' => $textHeight);
			switch ($intPos) {
				case Gd::POS_UPPER_LEFT:
					break;
				case Gd::POS_CENTER_LEFT:
					$pos['y'] = intval(($this->height + $textHeight)/2);
					break;
				case Gd::POS_LOWER_LEFT:
					$pos['y'] = $this->height;
					break;
				case Gd::POS_UPPER_CENTER:
					$pos['x'] = intval(($this->width - $textWidth)/2);
					break;
				case Gd::POS_CENTER:
					$pos['x'] = intval(($this->width - $textWidth)/2);
					$pos['y'] = intval(($this->height + $textHeight)/2);
					break;
				case Gd::POS_LOWER_CENTER:
					$pos['x'] = intval(($this->width - $textWidth)/2);
					$pos['y'] = $this->height;
					break;
				case Gd::POS_UPPER_RIGHT:
					$pos['x'] = $this->width - $textWidth;
					break;
				case Gd::POS_CENTER_RIGHT:
					$pos['x'] = $this->width - $textWidth;
					$pos['y'] = intval(($this->height + $textHeight)/2);
					break;
				case Gd::POS_LOWER_RIGHT:
					$pos['x'] = $this->width - $textWidth;
					$pos['y'] = $this->height;
					break;
				default:
					break;
			}
		}
		
		$imgnew = Gd::createImage($this->width, $this->height);
		imagesettile($imgnew, $this->handle);
		imagefilledrectangle($imgnew,0,0,$this->width,$this->height,IMG_COLOR_TILED);
		$text2 = imagecolorallocate($imgnew,$rgb->r, $rgb->g, $rgb->b);
		imagettftext($imgnew,$font['size'],$font['angle'],$pos['x'],$pos['y'],$text2,$font['fontfile'],$text);
		return $imgnew;
	}
	
	/**
	 * 图片水印
	 */
	public function watermark($filename, $pos = 0, $transparentColor = null, $clone = true)
	{
		$watermark = Gd::loadImage($filename);
		
		if (is_array($pos)) {
			$pos['x'] || $pos['x'] = 0;
			$pos['y'] || $pos['y'] = 0;
		} else {
			$intPos = intval($pos);
			$pos = array('x' => 0, 'y' => 0);
			switch ($intPos) {
				case Gd::POS_UPPER_LEFT:
					break;
				case Gd::POS_CENTER_LEFT:
					$pos['y'] = intval(($this->height - $watermark->height)/2);
					break;
				case Gd::POS_LOWER_LEFT:
					$pos['y'] = $this->height - $watermark->height;
					break;
				case Gd::POS_UPPER_CENTER:
					$pos['x'] = intval(($this->width - $watermark->width)/2);
					break;
				case Gd::POS_CENTER:
					$pos['x'] = intval(($this->width - $watermark->width)/2);
					$pos['y'] = intval(($this->height - $watermark->height)/2);
					break;
				case Gd::POS_LOWER_CENTER:
					$pos['x'] = intval(($this->width - $watermark->width)/2);
					$pos['y'] = $this->height - $watermark->height;
					break;
				case Gd::POS_UPPER_RIGHT:
					$pos['x'] = $this->width - $watermark->width;
					break;
				case Gd::POS_CENTER_RIGHT:
					$pos['x'] = $this->width - $watermark->width;
					$pos['y'] = intval(($this->height - $watermark->height)/2);
					break;
				case Gd::POS_LOWER_RIGHT:
					$pos['x'] = $this->width - $watermark->width;
					$pos['y'] = $this->height - $watermark->height;
					break;
				default:
					break;
			}
		}
		
		if ($transparentColor) {
			$rgb = Gd::hex2rgb($transparentColor);
			$transparency = imagecolorallocate($watermark->handle , $rgb->r , $rgb->g , $rgb->b);
			$transparency = imagecolortransparent($watermark->handle, $transparency);
		}
		
		$handle = $clone ? Gd::copyImageHandle($this->handle) : $this->handle;
		
		imagecopy($handle, $watermark->handle, $pos['x'], $pos['y'], 0, 0, $watermark->width, $watermark->height);
		return $handle;
	}
	
	public function cropImage($x = 0, $y = 0, $new_w = 0, $new_h = 0)
	{
		$new_w || $new_w = $this->width - $x;
		$new_h || $new_h = $this->height - $y;
		return Gd::copyCreateHandle($this->handle, 0, 0, $x, $y, $new_w, $new_h, $new_w, $new_h);
	}

	public function mergeImage($filename, $srcx, $srcy, $opacity = 100)
	{
		$newimage = Gd::loadImage($filename);
		return $this->mergeImages($newimage, $srcx, $srcy, $opacity);
	}

	public function mergeImages($newimage, $srcx, $srcy, $opacity = 100)
	{
		$handle = Gd::copyImageHandle($this->handle);
		Gd::saveAlpha($handle);
		if ($opacity < 100) {
			@ImageCopyMerge($handle,$newimage->handle,$srcx,$srcy,0,0,$newimage->width,$newimage->height,$opacity);
		} else {
			Gd::copyhandle($handle,$newimage->handle,$srcx,$srcy,0,0,$newimage->width,$newimage->height,$newimage->width,$newimage->height);

		}

		return $handle;
	}
	
	public function mergeColor($color, $opacity)
	{
		$newimage = ImageCreate($this->width,$this->height);
		$rgb = Gd::hex2rgb($color);
		$mergecolor = ImageColorAllocate($newimage, $rgb->r, $rgb->g, $rgb->b);
		ImageCopyMerge($this->handle,$newimage,0,0,0,0,$this->width,$this->height,$opacity);
		return $this->handle;
	}
	
	public function gammaCorrect($gamma)
	{
		ImageGammaCorrect($this->handle, 1.0, $gamma);
		return $this->handle;
	}
	
	public function changeColor($hue=0, $sat=0, $lum=0, $red=0, $green=0, $blue=0)
	{
		$imgnew = Gd::createImage($this->width, $this->height);
		// horizontal
		for ($i = 0; $i < $this->width; $i++) {
			// vertical
			for ($j=0;$j<$this->height;$j++) {
				$color = imagecolorat($this->handle, $i, $j);
				$rgb = imagecolorsforindex($this->handle, $color);
				$hls = $this->rgb2hls($rgb["red"], $rgb["green"], $rgb["blue"]);
				$hls->h += $hue * $hls->h;
				$hls->l += $lum * $hls->l;
				$hls->s += $sat * $hls->s;
				if ($hls->h > 255) $hls->h = 255;
				if ($hls->h < 0) $hls->h = 0;
				if ($hls->l > 1) $hls->l = 1;
				if ($hls->l < 0) $hls->l = 0;
				if ($hls->s > 1) $hls->s = 1;
				if ($hls->s < 0) $hls->s = 0;
				$rgb = $this->hls2rgb($hls->h, $hls->l, $hls->s);
				
				$rgb->r += $red * $rgb->r;
				$rgb->g += $green * $rgb->g;
				$rgb->b += $blue * $rgb->b;
				
				if ($rgb->r > 255)$rgb->r = 255;
				if ($rgb->r < 0)$rgb->r = 0;
				if ($rgb->g > 255)$rgb->g = 255;
				if ($rgb->g < 0)$rgb->g = 0;
				if ($rgb->b > 255)$rgb->b = 255;
				if ($rgb->b < 0)$rgb->b = 0;

				$newcol = imagecolorresolve($imgnew, $rgb->r, $rgb->g, $rgb->b);
				imagesetpixel($imgnew,$i,$j,$newcol);
			}
		}
		return $imgnew;
	}
}