<?php
/**
 * The expansion of the system files
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2005 - 2017, Fitz Zhang <alacner@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Filesystem.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_Filesystem
{
    const FOLDER = 1;
    const FILE = 2;
    const BOTH = 3;

    public static $units = ['B', 'Kb', 'Mb', 'Gb', 'Tb', 'PB', 'EB', 'ZB', 'YB'];//First letter must be capitalized

    /**
     * Get temp directory
     *
     * @return mixed|null
     */
    public static function getTempDir()
    {
        static $tempDir;
        $tempDir || $tempDir = self::concat(Tii::get('tii.temp_dir', sys_get_temp_dir()), PHP_SAPI);
        return $tempDir;
    }

    /**
     * Get directory which is to save permanent data
     *
     * @return string
     */
    public static function getDataDir()
    {
        static $dir;
        $dir || $dir = self::concat(Tii::get('tii.data_dir', self::getTempDir()), PHP_SAPI);
        return $dir;
    }

    /**
     * Concat directory
     *
     * @return string
     */
    public static function concat()
    {
        $dirsAry = [];
        foreach(func_get_args() as $dir) {
            empty($dir) || $dirsAry[] = rtrim($dir, '\\/');
        }

        return implode(DIRECTORY_SEPARATOR, $dirsAry);
    }

    /**
     * Explode directory
     *
     * @param $path
     * @return array
     */
    public static function explode($path)
    {
        return explode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Get filename extension
     *
     * @param $file
     * @return string
     */
    public static function getFilenameExt($file)
    {
        return strtolower(substr(strrchr($file, "."), 1));
    }

    /**
     * Get basename without extension
     * like: /path/to/filename.ext => filename
     *
     * @param $file
     * @return string
     */
    public static function getBasenameWithoutExt($file)
    {
        return preg_replace('|\.([^.]*$)|', '', basename($file));
    }

    /**
     * The relative path recursively all folders
     * @see getFiles
     */
    public static function getRelativePathFiles($path, array $exts = [], $recursive = true, $filter = self::FILE)
    {
        $i = strlen(realpath($path));
        return array_map(function($s) use ($i) {return substr($s, $i+1);}, self::getFiles($path, $exts, $recursive, $filter));
    }

    /**
     * The relative path recursively all files
     * @see getFiles
     */
    public static function getRelativePathFolders($path, $recursive = true)
    {
        return self::getRelativePathFiles($path, [], $recursive, self::FOLDER);
    }

    /**
     *  Recursive folder for all folders
     *
     * @see getFiles
     * @param $path
     * @param bool $recursive
     * @return array
     */
    public static function getFolders($path, $recursive = true)
    {
        return self::getFiles($path, [], $recursive, self::FOLDER);
    }

    /**
     * Recursive folder for all files
     *
     * @param $path
     * @param array $exts, default: all extensions
     * @param bool $recursive default: true
     * @param int $filter self::FILE
     * @return array
     */
    public static function getFiles($path, array $exts = [], $recursive = true, $filter = self::FILE)
    {
        $files = [];

        if (is_dir($path) && $handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if (is_dir($folder = self::concat($path, $file))) {
                        Tii_Math::getStatus($filter, 1) && $files[] = realpath($folder);
                        if (!$recursive) continue;//A folder
                        foreach(self::getFiles(self::concat($path, $file), $exts, $recursive, $filter) as $filename) {
                            $files[] = realpath($filename);
                        }
                    } elseif(Tii_Math::getStatus($filter, 2)) {
                        if (empty($exts) || in_array(self::getFilenameExt($file), $exts)) {
                            $files[] = realpath(self::concat($path, $file));
                        }
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Tests for file writability
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute.  is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     */
    public static function isWritable($file)
    {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR == '/' && @ini_get("safe_mode") == false) {
            return is_writable($file);
        }

        // For windows servers and safe_mode "on" installations we'll actually
        // write a file then read it.  Bah...
        if (is_dir($file)) {
            $file = rtrim($file, '/').'/'.md5(mt_rand(1,100).mt_rand(1,100));

            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif ( ! is_file($file) OR ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }

        fclose($fp);
        return true;
    }

    /**
     * mkdir
     *
     * @param $pathname
     * @param int $mode
     * @return bool
     */
    public static function mkdir($pathname, $mode = 0777)
    {
        is_dir($pathname) || clearstatcache();
        return is_dir($pathname) ? false : mkdir($pathname, $mode, true);
    }

    /**
     * rmdir
     *
     * @param $pathname
     * @param $recursive
     * @return bool
     */
    public static function rmdir($pathname, $recursive = false)
    {
        is_dir($pathname) || clearstatcache();
        if (!is_dir($pathname)) return false;

        if ($recursive) {
            $dir = opendir($pathname);
            while(false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    $fdir = $pathname . '/' . $file;
                    if (is_dir($fdir)) {
                        self::rmdir($fdir, $recursive);
                    } else {
                        unlink($fdir);
                    }
                }
            }
            closedir($dir);
        }

        return rmdir($pathname);
    }

    /**
     * Deletes filename
     *
     * @param $filename
     * @return bool
     */
    public static function unlink($filename)
    {
        is_file($filename) || clearstatcache();
        return is_file($filename) && unlink($filename);
    }

    /**
     * Sets access and modification time of file
     *
     * @param $filename
     * @param null $time
     * @param null $atime
     * @return bool
     */
    public static function touch($filename, $time = null, $atime = null)
    {
        return touch($filename, $time, $atime);
    }

    /**
     * Copies file
     *
     * @param $source
     * @param $dest
     * @return bool
     */
    public static function copy($source, $dest)
    {
        return copy($source, $dest);
    }

    /**
     * Create file with unique file name
     *
     * @param string $prefix
     * @param string $suffix
     * @return string
     */
    public static function tempnam($prefix = 'tii.', $suffix = '')
    {
        return tempnam(self::getTempDir(), $prefix) . $suffix;
    }

    /**
     * Create file in temp dir, like tempnam
     * args: $filename[, $path1[, $path2[,...]]]
     *
     * @return string
     */
    public static function tempfile()
    {
        $args = func_get_args();
        $filename = array_shift($args);
        array_unshift($args, self::getTempDir());
        $args[] = $filename;
        $filename = call_user_func_array('self::concat', $args);
        self::mkdir(dirname($filename));
        return $filename;
    }

    /**
     * If the hash of positive and negative is the same, the probability of that conflict should be very small
     * EXT[2-3]_LINK_MAX => 32000 (inode number)
     *
     * @param $key
     * @param string $namespace
     * @param $suffix
     * @param null $path
     * @return mixed
     */
    public static function hashfile($key, $namespace = 'tii', $suffix = '', $path = NULL)
    {
        static $cached = [];

        if (!isset($cached[$key][$namespace][$suffix][$path])) {
            $hash = md5($key).md5(strrev($key));
            $cached[$key][$namespace][$suffix][$path] = self::concat(
                $path ?: self::getTempDir(),
                $namespace,
                substr($hash, 0, 3),//16*16*16 => 4096
                substr($hash, 4, 3),//4096 * 4096 => 16777216 can store hashed number
                $hash
            ) . $suffix;
            self::mkdir(dirname($cached[$key][$namespace][$suffix][$path]));
        }

        return $cached[$key][$namespace][$suffix][$path];
    }

    /**
     * use LOCK_EX to lock mutex limit
     *
     * @param $key
     * @param callable $locked
     * @param callable $unlocked
     */
    public static function locker($key, $locked = NULL, $unlocked = NULL)
    {
        $fp = fopen(self::hashfile($key, 'filesystem/mutex'), 'w+');
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            if (is_callable($locked)) call_user_func($locked);
            flock($fp, LOCK_UN);
        } else {
            if (is_callable($unlocked)) call_user_func($unlocked);
        }
        fclose($fp);
    }

    /**
     * According to the callback function for caching
     *
     * @param $cacheName
     * @param int $expired
     * @param callable $function
     * @param array $param_arr
     * @param bool $isUseExpired
     * @return bool|mixed
     */
    public static function cached($cacheName, $expired = 0, $function = NULL, array $param_arr = [], $isUseExpired = true)
    {
        try {
            return call_user_func_array('self::_cached', func_get_args());
        } catch (Exception $e) {
            Tii_Logger::warn("file cache `$cacheName' exception:". $e->getMessage());
            return Tii::call($function, $param_arr);
        }
    }

    private static function _cached($cacheName, $expired = 0, $function = NULL, array $param_arr = [], $isUseExpired = true)
    {
        $filename = self::hashfile($cacheName, 'filesystem/cached');

        if ($expired < 0) {//Remove cached file less than 0
            $data = Tii::call($function, $param_arr);
            self::unlink($filename);
            return $data;
        }

        $fileExist = is_file($filename) && (($mtime = intval(filemtime($filename))) > 0);
        if ($fileExist && (!$expired || ((time() - $mtime) <= $expired))) {//use cached data
            Tii_Logger::debug("got cache: `$cacheName' via file: `$filename'");
            return unserialize(file_get_contents($filename));
        }

        $cachedData = NULL;
        self::locker($cacheName, function() use (&$cachedData, $filename, $function, $param_arr){
            ignore_user_abort(true);
            $cachedData = Tii::call($function, $param_arr);
            if (!is_null($cachedData)) {
                $cachedData = serialize($cachedData);
                file_put_contents($filename, $cachedData, LOCK_EX);
            }
            ignore_user_abort(false);
        }, function() use (&$cachedData, $filename, $expired) {
            usleep(round(rand(0, 100) * 1000)); //0-100 miliseconds
            $fileExist = (($mtime = intval(filemtime($filename))) > 0);
            if ($fileExist && (!$expired || ((time() - $mtime) <= $expired))) {//retry
                $cachedData = file_get_contents($filename);
            }
        });

        if (
            is_null($cachedData)//got data NULL via source
            && $isUseExpired//allow use expired data
            && $fileExist//expired data exists
        ) {
            Tii_Logger::warn("got expired cache: `$cacheName' via file: `$filename'");
            $cachedData = file_get_contents($filename);
        }
        if ($cachedData) {
            return unserialize($cachedData);
        } else {
            return NULL;
        }
    }

    /**
     * Returns a human readable memory size
     *
     * @param $bytes
     * @param string $format  The format to display (printf format)
     * @param int $round
     * @param int $mod
     * @return string
     */
    public static function format($bytes, $format = '%.2f%s', $round = 3, $mod = 1024)
    {
        for ($i = 0; $bytes > $mod; $i++) {
            $bytes /= $mod;
        }

        if (0 === $i) {
            $format = preg_replace('/(%.[\d]+f)/', '%d', $format);
        }

        return sprintf($format, round($bytes, $round), self::$units[$i]);
    }

    /**
     * Convert a size from human readable format (with a unit like K, M, G for Kilobytes, Megabytes, etc.)
     * 1m => 1048576, 2g => 2147483648,...
     *
     * @param $val
     * @param int $mod
     * @return int
     */
    public static function bytes($val, $mod = 1024)
    {
        $val = trim(str_replace('B', '', strtoupper($val)));
        if (!is_numeric($val)) {
            $pow = array_search(substr($val, -1), array_map(function($u){return $u{0};}, self::$units));
            $pow || $pow = 0;
            $val *= pow($mod, $pow);
        }
        return intval($val);
    }
}