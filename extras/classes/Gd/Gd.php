<?php
/**
 * Gd 扩展函数
 * @author Zhihua Zhang <alacner@gmail.com> 2011-04-21
 * @version $Id: Gd.php 478 2012-04-03 07:17:21Z yametei $
 */

class Gd_Exception extends Exception {}

class Gd
{
	
	const POS_UPPER_LEFT = 0;//左上
	const POS_CENTER_LEFT = 1;//左中
	const POS_LOWER_LEFT = 2;//左下
	const POS_UPPER_CENTER = 3;//中上
	const POS_CENTER = 4;//中
	const POS_LOWER_CENTER = 5;//中下
	const POS_UPPER_RIGHT = 6;//右上
	const POS_CENTER_RIGHT = 7;//右中
	const POS_LOWER_RIGHT = 8;//右下

	/**
	 * 拷贝副本，并返回该副本的句柄
	 * @param $im
	 * @return resource
	 */
	public static function copyImageHandle(&$im)
	{
		$tmpfname = tempnam("/tmp", "image");
		ImagePng($im, $tmpfname);
		$handle = ImageCreateFromString(file_get_contents($tmpfname));
		unlink($tmpfname);
		return $handle;
	}

	/**
	 * load image from data
	 * @param string $data
	 * @throws Gd_Exception
	 * @return array(handle, width, height)
	 */
	public static function loadImageFromString($data)
	{
		$handle = ImageCreateFromString($data);
		if (!is_resource($handle)) {
			throw new Gd_Exception('图片格式不支持或者图像已损坏！');
		}

		$object = new stdClass();
		$object->handle = $handle;
		$object->width = ImageSX($handle);
		$object->height = ImageSY($handle);

		return $object;
	}
	
	/**
	 * load image file
	 * @param string $filename
	 * @throws Gd_Exception
	 * @return array(handle, width, height) 
	 */
	public static function loadImage($filename)
	{
		return self::loadImageFromString(file_get_contents($filename));
	}

	/**
	 * 创建一个图片
	 * @param $w
	 * @param $h
	 * @return resource
	 */
	public static function createImage($w, $h)
	{
		if (function_exists("imagecreatetruecolor")) {
			$image = imagecreatetruecolor($w, $h);
			$color = imagecolorAllocate($image, 255, 255, 255);//分配一个白色
			imagefill($image, 0, 0, $color);// 从左上角开始填充白色
		} else {
			$image = imagecreate($w, $h);
			imagecolorallocate($image, 255, 255, 255);
		}
		return $image;
	}

	/**
	 * 保存图片
	 * @param $image 图片句柄
	 * @param $filename 保存的文件
	 * @param string $function
	 * @return mixed
	 */
	public static function save($image, $filename, $function = 'imagepng')
	{
		return call_user_func($function, $image, $filename);
	}

	/**
	 * 将图片句柄转换成图片内容
	 * @param $image
	 * @param string $function
	 * @return string
	 */
	public static function get($image, $function = 'imagepng')
	{
		$tmpfname = tempnam("/tmp", "image");
		self::save($image, $tmpfname, $function);
		$tmp = file_get_contents($tmpfname);
		unlink($tmpfname);
		return $tmp;
	}

	/**
	 * 拷贝图片
	 * @param $dst_im 目标句柄
	 * @param $src_im 来源句柄
	 * @param $dst_x 目标x坐标
	 * @param $dst_y 目标y坐标
	 * @param $src_x 来源x坐标
	 * @param $src_y 来源y坐标
	 * @param $dst_w 目标宽度
	 * @param $dst_h  目标高度
	 * @param $src_w 来源宽度
	 * @param $src_h 来源高度
	 */
	public static function copyHandle(&$dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)
	{
		if (function_exists("imagecopyresampled")) {
			ImageCopyResampled($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
		} else if (function_exists("imagecopyresized")) {
			ImageCopyResized($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
		} else {
			ImageCopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
		}
	}

	/**
	 * 创建并拷贝图片内容
	 * @param $src_im
	 * @param $dst_x
	 * @param $dst_y
	 * @param $src_x
	 * @param $src_y
	 * @param $dst_w
	 * @param $dst_h
	 * @param $src_w
	 * @param $src_h
	 * @return resource
	 */
	public static function copyCreateHandle(&$src_im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)
	{
		$dst_im = self::createImage($dst_w, $dst_h);
		self::copyHandle($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
		return $dst_im;
	}

	/**
	 * 设置图片的饱和度
	 * @param $im
	 * @param float $saturation
	 */
	public static function saturation(&$im, $saturation = 0.9)//[0,10]
	{
		$L = imagesx($im);
		$H = imagesy($im);
		
		for($j=0;$j<$H;$j++){
			for($i=0;$i<$L;$i++){ 
				$rgb = imagecolorat($im, $i, $j);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				
				$hsb = self::rgb2hsb($r,$g,$b);
				$hsb->s = $saturation*$hsb->s;
				$hsb->s = max(0.0,min($hsb->s,1.0));
				
				$rgb2 = self::hsb2rgb($hsb->h, $hsb->s,$hsb->b);
				$hsbrgb = imagecolorallocate($im, $rgb2->r, $rgb2->g, $rgb2->b);
				//$result = (($rgb & 0xff000000)|($hsbrgb));
				$hsbrgb = (($rgb & 0xff000000)|($hsbrgb) | (0xff000000));
				
				imagesetpixel($im, $i, $j, $hsbrgb);
			}
		}
	}

	/**
	 * 设置图片的alpha
	 * @param $im
	 */
	public static function saveAlpha(&$im) {
		ImageAlphaBlending($im, true);
		ImageSaveAlpha($im, false);
		ImageSaveAlpha($im, true);
	}

	/**
	 * 获取图片的颜色hex值
	 * @param $im
	 * @param $xpos
	 * @param $ypos
	 * @return string
	 */
	public static function getHexColor(&$im, $xpos,$ypos)
	{
		$color = imagecolorat($im, $xpos, $ypos);
		$rgb = imagecolorsforindex($im, $color);
		
		$hred = ($rgb["red"]>0) ? str_pad(dechex($rgb["red"]), 2, '0') : "00";
		$hgreen = ($rgb["green"]>0) ? str_pad(dechex($rgb["green"]), 2, '0') : "00";
		$hblue = ($rgb["blue"]>0) ? str_pad(dechex($rgb["blue"]), 2, '0') : "00";
		return strtoupper($hred.$hgreen.$hblue);
	}
	
	/**
	 * 将16进制的颜色转换成10进制的（R，G，B）
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
			throw new Image_Exception('传入的16进制颜色值有误');
		}
		
		$rgb = new stdClass();
		$rgb->r = hexdec($r);
		$rgb->g = hexdec($g);
		$rgb->b = hexdec($b);
		
		return $rgb;
	}
	
	public static function valore($n1, $n2, $hue)
	{
		if($hue>=360) $hue = $hue-360;
		if($hue<0) $hue = $hue+360;
		
		if($hue>=240) $result = $n1;
		if($hue<240) $result = $n1+($n2-$n1)*(240-$hue)/60;
		if($hue<180) $result = $n2;
		if($hue<60) $result = $n1+($n2-$n1)*$hue/60;
		
		return($result);
	}
	
	public static function rgb2hls($r, $g, $b)
	{
		$c1 = $r/255;
		$c2 = $g/255;
		$c3 = $b/255;
		
		$kmin = min($c1,$c2,$c3);
		$kmax = max($c1,$c2,$c3);
		
		$l = ($kmax+$kmin)/2;
		
		if ($kmax == $kmin) {
			$s = 0;
			$h = 0;
		} else {
			if($l<=0.5) $s = ($kmax-$kmin)/($kmax+$kmin);
			else $s = ($kmax-$kmin)/(2-$kmax-$kmin);
			
			$delta = $kmax-$kmin;
			if ($kmax==$c1) $h = ($c2-$c3)/$delta;
			if ($kmax==$c2) $h = 2+($c3-$c1)/$delta;
			if ($kmax==$c3) $h = 4+($c1-$c2)/$delta;
			
			$h = $h*60;

			if ($h<0) $h = $h+360;
		}
		
		$out = new stdClass();
		$out->h = $h;
		$out->s = $s;
		$out->l = $l;
		
		return($out);
	}

	public static function hls2rgb($h, $l, $s)
	{
		if ($l<=0.5) $m2 = $l*(1+$s);
		else $m2 = $l+$s*(1-$l);
		
		$m1 = 2*$l-$m2;
		
		$c1 = self::valore($m1,$m2,$h+120);
		$c2 = self::valore($m1,$m2,$h);
		$c3 = self::valore($m1,$m2,$h-120);
		
		if ($s==0 && $h==0) {
			$c1 = $l;
			$c2 = $l;
			$c3 = $l;
		}
		$r = round($c1*255);
		$g = round($c2*255);
		$b = round($c3*255);
		
		$out = new stdClass();
		$out->r = $r;
		$out->g = $g;
		$out->b = $b;

		return($out);
	}

	/**
	 * RGB Values:Number 0-255，HSV Results:Number 0-1
	 * @param $R
	 * @param $G
	 * @param $B
	 * @return stdClass
	 */
	public static function rgb2hsv ($R, $G, $B)
	{
		$HSL = array();
		
		$var_R = ($R / 255);
		$var_G = ($G / 255);
		$var_B = ($B / 255);
		
		$var_Min = min($var_R, $var_G, $var_B);
		$var_Max = max($var_R, $var_G, $var_B);
		$del_Max = $var_Max - $var_Min;
	
		$V = $var_Max;
	
		if ($del_Max == 0) {
			$H = 0;
			$S = 0;
		} else { 
			$S = $del_Max / $var_Max;
	
			$del_R = ( ( ( $var_Max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
			$del_G = ( ( ( $var_Max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
			$del_B = ( ( ( $var_Max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
	
			if ($var_R == $var_Max) $H = $del_B - $del_G;
			else if ($var_G == $var_Max) $H = ( 1 / 3 ) + $del_R - $del_B;
			else if ($var_B == $var_Max) $H = ( 2 / 3 ) + $del_G - $del_R;
	
			if ($H<0) $H++;
			if ($H>1) $H--;
		} 
		
		$out = new stdClass();
		$out->h = $H;
		$out->s = $S;
		$out->v = $V;

		return($out);
	}

	/**
	 * HSV Values:Number 0-1,RGB Results:Number 0-255
	 * @param $H
	 * @param $S
	 * @param $V
	 * @return stdClass
	 */
	public static function hsv2rgb ($H, $S, $V)
	{
		$RGB = array();
	
		if($S == 0) { 
			$R = $G = $B = $V * 255;
		} else { 
			$var_H = $H * 6;
			$var_i = floor( $var_H );
			$var_1 = $V * ( 1 - $S );
			$var_2 = $V * ( 1 - $S * ( $var_H - $var_i ) );
			$var_3 = $V * ( 1 - $S * (1 - ( $var_H - $var_i ) ) );
	
			if ($var_i == 0) {
				$var_R = $V;
				$var_G = $var_3;
				$var_B = $var_1;
			} else if ($var_i == 1) {
				$var_R = $var_2;
				$var_G = $V;
				$var_B = $var_1;
			} else if ($var_i == 2) {
				$var_R = $var_1;
				$var_G = $V;
				$var_B = $var_3;
			} else if ($var_i == 3) {
				$var_R = $var_1;
				$var_G = $var_2;
				$var_B = $V;
			} else if ($var_i == 4) {
				$var_R = $var_3;
				$var_G = $var_1;
				$var_B = $V;
			} else {
				$var_R = $V;
				$var_G = $var_1;
				$var_B = $var_2;
			}
			
			$R = $var_R * 255;
			$G = $var_G * 255;
			$B = $var_B * 255;
		}
		
		$out = new stdClass();
		$out->r = $R;
		$out->g = $G;
		$out->b = $B;

		return($out);
	}
	
	public static function rgb2hsb($new_r, $new_g, $new_b)
	{
		$arrayHSB = array($new_r,$new_g,$new_b);
		$h = 0.0 ;
		$minRGB = min($new_r,$new_g,$new_b);
		$maxRGB = max($new_r,$new_g,$new_b);
	
		$delta = ($maxRGB - $minRGB);
		$bright = $maxRGB;
		if ($maxRGB != 0.0) {$s = $delta / $maxRGB;}
		else {$s = 0.0;$h=-1;}
		if ($s != 0.0){
			if ($new_r == $maxRGB) {
				$h = ($new_g - $new_b) / $delta;
			}
			else {
				if ($new_g == $maxRGB) {
					$h = 2.0 + ($new_b - $new_r) / $delta;
				}
				else {
					if ($new_b == $maxRGB) {
						$h = 4.0 + ($new_r - $new_g) / $delta;
					}
				}     
			}
		}  
		else {
			$h = -1.0;
		} 
		$h = $h * 60.0 ;
		if ($h < 0.0) {$h = $h + 360.0;}
		
		$out = new stdClass();
		$out->h = $h;
		$out->s = $s;
		$out->b = $bright;

		return($out);
	}
	
	public static function hsb2rgb($new_hue,$new_saturation,$new_bright)
	{
		$arrayRGB= array($new_hue,$new_saturation,$new_bright);
		if($new_saturation == 0.0) {
			$r=$new_bright;
			$g=$new_bright;
			$b=$new_bright;
		}   
	
		$new_hue = $new_hue/60.0;
		$m = intval($new_hue);
		$f = $new_hue - $m;
		$p = $new_bright * (1.0 - $new_saturation);
		$q = $new_bright * (1.0 - $new_saturation * $f);
		$t = $new_bright * (1.0 - $new_saturation * (1.0 - $f));
		$r = $g = $b = 0;
	
		switch($m) {
			case 0:
				$r = $new_bright;
				$g = $t;
				$b = $p;
				break;
			case 1:
				$r = $q;
				$g = $new_bright;
				$b = $p;
				break;
			case 2:
				$r = $p;
				$g = $new_bright;
				$b = $t;
				break;
			case 3:
				$r = $p;
				$g = $q;
				$b = $new_bright;
				break;
			case 4:
				$r = $t;
				$r = $p;
				$r = $new_bright;
				break;
			default:      // case 5:
				$r= $new_bright;
				$g = $p;
				$b = $q;
		}
		
		$out = new stdClass();
		$out->r = $r;
		$out->g = $g;
		$out->b = $b;

		return($out);
	}
}