<?php
/**
 * Ftp class
 *
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: ZipFile.php 6404 2016-08-05 06:55:18Z alacner $
 */

final class Tattoo_ZipFile
{
	var $zipfilename;
	var $dir = "./";
	var $datasec = array();
	var $ctrl_dir = array();
	var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
	var $old_offset = 0;

	public function __construct($zipfilename, $dir = "./")
	{
		$this->zipfilename = $zipfilename;
		empty($dir) || $this->dir = $dir;
	}

	public function save()
	{
		if (@function_exists('gzcompress')) {
			$curdir = getcwd();
			if (is_array($this->dir)) {
				$filelist = $this->dir;
			} else {
				$filelist = $this->GetFileList($this->dir);
			}
			if (count($filelist)>0) {
				foreach($filelist as $filename) {
					if (is_file($filename))	{
						$fd = fopen ($filename, "r");
						$content = fread($fd, filesize ($filename));
						fclose ($fd);
						if (is_array($this->dir)) $filename = basename($filename);
						$this->addFile($content, $filename);
					}
				}
				$out = $this->file();
				$fp = fopen($this->zipfilename, "w");
				fwrite($fp, $out, strlen($out));
				fclose($fp);
			}
			return 1;
		}
		else return 0;
	}

	private function GetFileList($dir)
	{
		$file = [];
		if (file_exists($dir)) {
			if( substr( $dir, -1 ) != "/" || substr( $dir, -1 ) != "\\" ) {
				$dir .= "/";
			}
			$dh = opendir($dir);
			while($files = readdir($dh)) {
				if (($files!=".")&&($files!="..")) {
					if (is_dir($dir.$files)) {
						$file = array_merge($file, $this->GetFileList( $dir.$files ));
					}
					else $file[]= $dir.$files;
				}
			}
			closedir($dh);
		}
		$file[] = '';
		return $file;
	}

	private function unix2DosTime($unixtime = 0)
	{
		$timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);
		if ($timearray['year'] < 1980) {
			$timearray['year'] = 1980;
			$timearray['mon'] = 1;
			$timearray['mday'] = 1;
			$timearray['hours'] = 0;
			$timearray['minutes'] = 0;
			$timearray['seconds'] = 0;
		}
		return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
		($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
	}

	private function addFile($data, $name, $time = 0)
	{
		$name = str_replace('\\', '/', $name);
		$dtime = dechex($this->unix2DosTime($time));
		$hexdtime = '\x' . $dtime[6] . $dtime[7]
			. '\x' . $dtime[4] . $dtime[5]
			. '\x' . $dtime[2] . $dtime[3]
			. '\x' . $dtime[0] . $dtime[1];
		eval('$hexdtime = "' . $hexdtime . '";');

		$fr = "\x50\x4b\x03\x04";
		$fr .= "\x14\x00"; // ver needed to extract
		$fr .= "\x00\x00"; // gen purpose bit flag
		$fr .= "\x08\x00"; // compression method
		$fr .= $hexdtime; // last mod time and date

		// "local file header" segment
		$unc_len = strlen($data);
		$crc = crc32($data);
		$zdata = gzcompress($data);
		$c_len = strlen($zdata);
		$zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
		$fr .= pack('V', $crc); // crc32
		$fr .= pack('V', $c_len); // compressed filesize
		$fr .= pack('V', $unc_len); // uncompressed filesize
		$fr .= pack('v', strlen($name)); // length of filename
		$fr .= pack('v', 0); // extra field length
		$fr .= $name;

		// "file data" segment
		$fr .= $zdata;

		// "data descriptor" segment (optional but necessary if archive is not
		// served as file)
		$fr .= pack('V', $crc); // crc32
		$fr .= pack('V', $c_len); // compressed filesize
		$fr .= pack('V', $unc_len); // uncompressed filesize

		// add this entry to array
		$this->datasec[] = $fr;
		$new_offset = strlen(implode('', $this->datasec));

		// now add to central directory record
		$cdrec = "\x50\x4b\x01\x02";
		$cdrec .= "\x00\x00"; // version made by
		$cdrec .= "\x14\x00"; // version needed to extract
		$cdrec .= "\x00\x00"; // gen purpose bit flag
		$cdrec .= "\x08\x00"; // compression method
		$cdrec .= $hexdtime; // last mod time & date
		$cdrec .= pack('V', $crc); // crc32
		$cdrec .= pack('V', $c_len); // compressed filesize
		$cdrec .= pack('V', $unc_len); // uncompressed filesize
		$cdrec .= pack('v', strlen($name) ); // length of filename
		$cdrec .= pack('v', 0 ); // extra field length
		$cdrec .= pack('v', 0 ); // file comment length
		$cdrec .= pack('v', 0 ); // disk number start
		$cdrec .= pack('v', 0 ); // internal file attributes
		$cdrec .= pack('V', 32 ); // external file attributes - 'archive' bit set

		$cdrec .= pack('V', $this->old_offset ); // relative offset of local header
		$this->old_offset = $new_offset;

		$cdrec .= $name;

		$this->ctrl_dir[] = $cdrec;
	}

	private function file()
	{
		$data = implode('', $this->datasec);
		$ctrldir = implode('', $this->ctrl_dir);

		return
			$data .
			$ctrldir .
			$this->eof_ctrl_dir .
			pack('v', sizeof($this->ctrl_dir)) .
			pack('v', sizeof($this->ctrl_dir)) .
			pack('V', strlen($ctrldir)) .
			pack('V', strlen($data)) .
			"\x00\x00";
	}
}