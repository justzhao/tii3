<?php
/**
 * draw
 *
 * @author  Alacner Zhang <alacner@gmail.com>
 * @version $Id: Draw.php 6424 2016-08-09 05:53:43Z alacner $
 */
class Tattoo_Draw
{
	protected $sx = 0;
	protected $sy = 0;
	protected $pixels = [];

	public function __construct($sx = 80, $sy = 20)
	{
		$this->sx = $sx;
		$this->sy = $sy;
	}

	public function output()
	{

		//主程序

		$sx = 80;
		$sy = 21;
		$pixels = "";

		// 填充
		for ($h = 0; $h < $sy; $h++) {
			for ($w = 0; $w < $sx; $w++) {
				$r = 100 / $sx * $w + 155;
				$g = 100 / $sy * $h + 155;
				$b = 255 - (100 / ($sx + $sy) * ($w + $h));
				$pixels .= chr($r);
				$pixels .= chr($g);
				$pixels .= chr($b);
			}
		}

		$date = getdate();
		$s = $date["seconds"];
		$m = $date["minutes"];
		$h = $date["hours"];
		$digits = array(95, 5, 118, 117, 45, 121, 123, 69, 127, 125);
		$lines = array(1, 1, 1, 2, 0, 1, 0, 2, 1, 0, 1, 1, 0, 0, 0, 1, 0, 2, 1, 2, 0, 1, 1, 1, 0, 0, 1, 0);

		draw2digits(4, 2, $h);
		draw2digits(30, 2, $m);
		draw2digits(56, 2, $s);
		set_4pixel(0, 0, 0, 26, 7);
		set_4pixel(0, 0, 0, 26, 13);
		set_4pixel(0, 0, 0, 52, 7);
		set_4pixel(0, 0, 0, 52, 13);

		// 创建循环冗余码校验表
		$z = -306674912;  // = 0xedb88320
		for ($n = 0; $n < 256; $n++) {
			$c = $n;
			for ($k = 0; $k < 8; $k++) {
				$c2 = ($c >> 1) & 0x7fffffff;
				if ($c & 1) $c = $z ^ ($c2); else $c = $c2;
			}
			$crc_table[$n] = $c;
		}

		// PNG file signature
		$result = pack("c*", 137,80,78,71,13,10,26,10);

		// IHDR chunk data:
		//   width:              4 bytes
		//   height:             4 bytes
		//   bit depth:          1 byte (8 bits per RGB value)
		//   color type:         1 byte (2 = RGB)
		//   compression method: 1 byte (0 = deflate/inflate)
		//   filter method:      1 byte (0 = adaptive filtering)
		//   interlace method:   1 byte (0 = no interlace)
		$data = pack("c*", ($sx >> 24) & 255,
			($sx >> 16) & 255,
			($sx >> 8) & 255,
			$sx & 255,
			($sy >> 24) & 255,
			($sy >> 16) & 255,
			($sy >> 8) & 255,
			$sy & 255,
			8,
			2,
			0,
			0,
			0);
		add_chunk("IHDR");

		// 以下不敢乱翻译，请自行参考
		//    scanline:
		//        filter byte: 0 = none
		//        RGB bytes for the line
		//    the scanline is compressed with "zlib", method 8 (RFC-1950):
		//        compression method/flags code: 1 byte ($78 = method 8, 32k window)
		//        additional flags/check bits:   1 byte ($01: FCHECK = 1, FDICT = 0, FLEVEL = 0)
		//        compressed data blocks:        n bytes
		//            one block (RFC-1951):
		//                bit 0: BFINAL: 1 for the last block
		//                bit 1 and 2: BTYPE: 0 for no compression
		//                next 2 bytes: LEN (LSB first)
		//                next 2 bytes: one's complement of LEN
		//                LEN bytes uncompressed data
		//        check value:  4 bytes (Adler-32 checksum of the uncompressed data)
		//
		$len = ($sx * 3 + 1) * $sy;
		$data = pack("c*", 0x78, 0x01,
			1,
			$len & 255,
			($len >> 8) & 255,
			255 - ($len & 255),
			255 - (($len >> 8) & 255));
		$start = strlen($data);
		$i2 = 0;
		for ($h = 0; $h < $sy; $h++) {
			$data .= chr(0);
			for ($w = 0; $w < $sx * 3; $w++) {
				$data .= $pixels[$i2++];
			}
		}


		// calculate a Adler32 checksum with the bytes data[start..len-1]
		$s1 = 1;
		$s2 = 0;
		for ($n = $start; $n < strlen($data); $n++) {
			$s1 = ($s1 + ord($data[$n])) % 65521;
			$s2 = ($s2 + $s1) % 65521;
		}
		$adler = ($s2 << 16) | $s1;

		$data .= chr(($adler >> 24) & 255);
		$data .= chr(($adler >> 16) & 255);
		$data .= chr(($adler >> 8) & 255);
		$data .= chr($adler & 255);
		add_chunk("IDAT");

		// IEND: marks the end of the PNG-file
		$data = "";
		add_chunk("IEND");

		// 列印图象
	}

	public function set_4pixel($r, $g, $b, $x, $y)
	{
		$ofs = 3 * ($this->sx * $y + $x);
		$this->pixels[$ofs] = chr($r);
		$this->pixels[$ofs + 1] = chr($g);
		$this->pixels[$ofs + 2] = chr($b);
		$this->pixels[$ofs + 3] = chr($r);
		$this->pixels[$ofs + 4] = chr($g);
		$this->pixels[$ofs + 5] = chr($b);
		$ofs += 3 * $this->sx;
		$this->pixels[$ofs] = chr($r);
		$this->pixels[$ofs + 1] = chr($g);
		$this->pixels[$ofs + 2] = chr($b);
		$this->pixels[$ofs + 3] = chr($r);
		$this->pixels[$ofs + 4] = chr($g);
		$this->pixels[$ofs + 5] = chr($b);
	}

    //生成数字图象的函数
    public function draw2digits($x, $y, $number)
    {
	    draw_digit($x, $y, (int) ($number / 10));
	    draw_digit($x + 11, $y, $number % 10);
	}

    public function draw_digit($x, $y, $digit)
    {
	    global $sx, $sy, $pixels, $digits, $lines;
	
	    $digit = $digits[$digit];
	    $m = 8;
	    for ($b = 1, $i = 0; $i < 7; $i++, $b *= 2) {
		        if (($b & $digit) == $b) {
			        $j = $i * 4;
			        $x0 = $lines[$j] * $m + $x;
			        $y0 = $lines[$j + 1] * $m + $y;
			        $x1 = $lines[$j + 2] * $m + $x;
			        $y1 = $lines[$j + 3] * $m + $y;
			        if ($x0 == $x1) {
				            $ofs = 3 * ($sx * $y0 + $x0);
				            for ($h = $y0; $h <= $y1; $h++, $ofs += 3 * $sx) {
					            $pixels[$ofs] = chr(0);
					            $pixels[$ofs + 1] = chr(0);
					            $pixels[$ofs + 2] = chr(0);
					            }
				        } else {
				            $ofs = 3 * ($sx * $y0 + $x0);
				            for ($w = $x0; $w <= $x1; $w++) {
					            $pixels[$ofs++] = chr(0);
					            $pixels[$ofs++] = chr(0);
					            $pixels[$ofs++] = chr(0);
					            }
				        }
			        }
		    }
	    }

    //将文字加入到图象中
    public function add_chunk($type)
    {
	    global $result, $data, $chunk, $crc_table;

	    // chunk :为层
	    // length: 4 字节: 用来计算 chunk
	    // chunk type: 4 字节
	    // chunk data: length bytes
	    // CRC: 4 字节:  循环冗余码校验
	
	    // copy data and create CRC checksum
	    $len = strlen($data);
	    $chunk = pack("c*", ($len >> 24) & 255,
        ($len >> 16) & 255,
        ($len >> 8) & 255,
        $len & 255);
    $chunk .= $type;
    $chunk .= $data;

    // calculate a CRC checksum with the bytes chunk[4..len-1]
    $z = 16777215;
    $z |= 255 << 24;
    $c = $z;
    for ($n = 4; $n < strlen($chunk); $n++) {
		        $c8 = ($c >> 8) & 0xffffff;
		        $c = $crc_table[($c ^ ord($chunk[$n])) & 0xff] ^ $c8;
    }
    $crc = $c ^ $z;

    $chunk .= chr(($crc >> 24) & 255);
    $chunk .= chr(($crc >> 16) & 255);
    $chunk .= chr(($crc >> 8) & 255);
    $chunk .= chr($crc & 255);

    // 将结果加到$result中
    $result .= $chunk;
    }
}