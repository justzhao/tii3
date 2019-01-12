<?php
/**
 * 安全的验证码
 * 支持：验证码文字旋转，使用不同字体，可加干扰码、可加干扰线、可使用中文、可使用背景图片
 * @author Alacner zhang
 * @version $Id: Abstract.php 6463 2016-08-11 15:18:28Z alacner $
 */
abstract class Tattoo_Captcha_Abstract
{
	protected $options = array(
		'key' => 'captcha_key',
		'expire' => 30,// 验证码过期时间（s）
		'height' => 56,// 验证码图片高
		'width' => 140,// 验证码图片宽
		'number' => 5,// 验证码位数
		'string' => '346789ABCDEFGHJKLMNPQRTUVWXYabcdefghjklmnpqrtuvwxy',//验证码中使用的字符，01IO容易混淆，建议不用
		'headers' => array(
			'Cache-Control' => array(
				'private, max-age=0, no-store, no-cache, must-revalidate',
				'post-check=0, pre-check=0'
			),
			'Pragma' => 'no-cache',
			'Content-type' => 'image/png',
		),
	);
	
	private $imageResource= null;
	private $fontColor = null;

	public function __construct($options = array()) {
		if (!extension_loaded('gd')) {
			throw new Desire_Exception('Class needs to load the gd extension');
		}
		
		$this->options = array_merge($this->options, $options);
	}
	
	public function getHeaders() {
		return $this->options['headers'];
	}
	
	protected function getCaptchaKey($id = 'default') {
		return $this->options['key'] . '_' . $id;
	}
	
	protected function getFiles($path, $exts = array()) {
		return Desire_Filesystem::getFiles($path, $exts);
	}

	protected function getExpire() {
		return $this->options['expire'];
	}
	
	abstract protected function saveCaptchaCode($key, $secode);
	abstract protected function getCaptchaCode($key);
	abstract protected function clearCaptchaCode($key);

	/**
	 * 验证验证码是否正确
	 *
	 * @param string $code 用户验证码
	 * @param string $id 多个同时验证的标识
	 * @return bool 用户验证码是否正确
	 */
	public function check($code, $id = 'default') {
		// 验证码不能为空
		if (empty($code)) {
			throw new Desire_Exception('captcha empty code');
		};
		$secode = $this->getCaptchaCode($this->getCaptchaKey($id));
		if (empty($secode) || !is_array($secode)) {
			throw new Desire_Exception('captcha secode error');
		}
		
		// 验证码过期
		if (Desire_Time::now() - $secode['time'] > $this->options['expire']) {
			throw new Desire_Exception('captcha expired');
		}

		if (strtoupper($code) === strtoupper($secode['code'])) {
			return true;
		}

		return false;
	}

	/**
	 * 输出验证码并把验证码的值保存
	 * 验证码保存的格式为：array('code' => '验证码值', 'time' => '验证码创建时间');
	 */
	public function entry($id = 'default') {
		//inject options from runtime.
		$this->options['source_dir'] = dirname(__FILE__).'/source'; //资源路径
		$this->options['font_size'] = min($this->options['height'], floor($this->options['width'] / $this->options['number'] / 5 * 4));
		$this->options['string_strlen'] = strlen($this->options['string']);

		$this->imageResource = imagecreate($this->options['width'], $this->options['height']);
		imagecolorallocate($this->imageResource, mt_rand(120,255), mt_rand(120,255), mt_rand(120,255));
		$this->fontColor = imagecolorallocate($this->imageResource, mt_rand(1,120), mt_rand(1,120), mt_rand(1,120));

		$ttfs = $this->getFiles($this->options['source_dir'] . '/fonts/', array('ttf'));

		$this->_writeNoise(); //画杂点

		// 绘验证码
		$code = array(); // 验证码
		$codeNX = 0; // 验证码第N个字符的左边距
		for ($i = 0; $i < $this->options['number']; $i++) {
			$ttf = $ttfs[array_rand($ttfs)];
			$code[$i] = $this->options['string'][mt_rand(0, $this->options['string_strlen']-1)];
			$codeNX += mt_rand($this->options['font_size']*0.7, $this->options['font_size']*1.2);
			// 写一个验证码字符
			imagettftext(
				$this->imageResource,
				$this->options['font_size'],
				mt_rand(0, 30),
				$codeNX,
				$this->options['font_size']*1.5,
				$this->fontColor,
				$ttf,
				$code[$i]
			);
		}

		$this->_writeCurve(); //绘干扰线

		$secode = array(
			'code' => join('', $code),
			'time' => Desire_Time::now(),
		);
		
		// 保存验证码
		$this->saveCaptchaCode($this->getCaptchaKey($id), $secode);

		$imageResource = $this->_writeDistortion(); //绘干扰线

		// 输出图像
		imagepng($imageResource);
		imagedestroy($imageResource);
		@imagedestroy($this->imageResource);
	}

	protected function _writeDistortion() {
		return $this->imageResource;
	}
	
	/** 
	 * 画一条由两条连在一起构成的随机正弦函数曲线作干扰线(你可以改成更帅的曲线函数) 
     *      
     *      高中的数学公式咋都忘了涅，写出来
	 *		正弦型函数解析式：y=Asin(ωx+φ)+b
	 *      各常数值对函数图像的影响：
	 *        A：决定峰值（即纵向拉伸压缩的倍数）
	 *        b：表示波形在Y轴的位置关系或纵向移动距离（上加下减）
	 *        φ：决定波形与X轴位置关系或横向移动距离（左加右减）
	 *        ω：决定周期（最小正周期T=2π/∣ω∣）
	 *
	 */
    protected function _writeCurve() {
    	$px = $py = 0;
    	
		// 曲线前部分
		$A = mt_rand(1, $this->options['height']/2);                  // 振幅
		$b = mt_rand(-$this->options['height']/4, $this->options['height']/4);   // Y轴方向偏移量
		$f = mt_rand(-$this->options['height']/4, $this->options['height']/4);   // X轴方向偏移量
		$T = mt_rand($this->options['height'], $this->options['width']*2);  // 周期
		$w = (2* M_PI)/$T;
						
		$px1 = 0;  // 曲线横坐标起始位置
		$px2 = mt_rand($this->options['width']/2, $this->options['width'] * 0.8);  // 曲线横坐标结束位置

		for ($px=$px1; $px<=$px2; $px=$px+ 0.9) {
			if ($w!=0) {
				$py = $A * sin($w*$px + $f)+ $b + $this->options['height']/2;  // y = Asin(ωx+φ) + b
				$i = (int) ($this->options['font_size']/5);
				while ($i > 0) {
				    imagesetpixel($this->imageResource, $px , $py + $i, $this->fontColor);  // 这里(while)循环画像素点比imagettftext和imagestring用字体大小一次画出（不用这while循环）性能要好很多
				    $i--;
				}
			}
		}
		
		// 曲线后部分
		$A = mt_rand(1, $this->options['height']/2);// 振幅
		$f = mt_rand(-$this->options['height']/4, $this->options['height']/4);// X轴方向偏移量
		$T = mt_rand($this->options['height'], $this->options['width']*2);// 周期
		$w = (2* M_PI)/$T;
		$b = $py - $A * sin($w*$px + $f) - $this->options['height']/2;
		$px1 = $px2;
		$px2 = $this->options['width'];

		for ($px=$px1; $px<=$px2; $px=$px+ 0.9) {
			if ($w!=0) {
				$py = $A * sin($w*$px + $f)+ $b + $this->options['height']/2;  // y = Asin(ωx+φ) + b
				$i = (int) ($this->options['font_size']/5);
				while ($i > 0) {
				    imagesetpixel($this->imageResource, $px, $py + $i, $this->fontColor);
				    $i--;
				}
			}
		}
	}
	
	/**
	 * 画杂点
	 * 往图片上写不同颜色的字母或数字
	 */
	protected function _writeNoise() {
		for($i = 0; $i < 10; $i++){
			//杂点颜色
		    $noiseColor = imagecolorallocate(
		                      $this->imageResource,
		                      mt_rand(150,225), 
		                      mt_rand(150,225), 
		                      mt_rand(150,225)
		                  );
			for($j = 0; $j < 5; $j++) {
				// 绘杂点
			    imagestring(
			        $this->imageResource,
			        5, 
			        mt_rand(-10, $this->options['width']),
			        mt_rand(-10, $this->options['height']), 
			        $this->options['string'][mt_rand(0, $this->options['string_strlen']-1)], // 杂点文本为随机的字母或数字
			        $noiseColor
			    );
			}
		}
	}
}