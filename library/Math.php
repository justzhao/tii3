<?php
/**
 * Math class
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
 * @version $Id: Math.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_Math
{
    /**
     * Since the increase effect only in a process effectively
     *
     * @return int
     */
    public static function getSequenceId()
    {
        static $sequenceId = 0;
        return ++$sequenceId;
    }

    /**
     * Get system bits
     *
     * @return float [32,64]
     */
    public static function getSystemBits()
    {
        static $bit = NULL;
        if (isset($bit)) return $bit;
        return $bit = (log(PHP_INT_MAX + 1, 2) + 1);
    }

    /**
     * Get from $bit a began to take $span represented by the value of $status
     *
     * @static
     * @param $status
     * @param $bit
     * @param int $span
     * @return int
     */
    public static function getStatus($status, $bit, $span = 1)
    {
        return $status >> --$bit & (1 << $span) - 1;
    }

    /**
     * Bitwise operator
     * NOTICE: In order to accuracy, use Tii_Dao_Common_QueryHelper::buildBinary in DAO.
     *
     * @param int $status
     * @param int $bit The start bit Binary
     * @param bool|int $val The status value，0-false, 1-true, other[$val <= (pow(2, $span) - 1)]
     * @param int $span span of bits
     * @return bool|int
     */
    public static function setStatus($status, $bit, $val = true, $span = 1)
    {
        if ($bit <= 0 || $span <= 0 || (intval($val) > (pow(2, $span) - 1))) return false;

        $val = sprintf('%0' . $span . 'b', $val); // to binary
        --$bit;
        $status &= ~((pow(2, $span) - 1)<< $bit); //clean all bits
        for ($i = $span - 1; $i >= 0; $i--) {
            if (isset($val[$i]) && $val[$i]) {
                $status |= (1<< $bit);
            } else {
                $status &= ~(1<<$bit);
            }
            ++$bit;
        }
        return $status;
    }

    /**
     * Get Float Length
     * Example: func(0.123456) => 6
     *
     * @param $num
     * @return int
     */
    public static function getFloatLength($num)
    {
        $count = 0;
        $temp = explode ('.', $num);
        if (count($temp) > 1) {
            $count = strlen(end($temp));
        }
        return $count;
    }

    /**
     * Calculate a path chain
     * Example: $chain = [];
     *          func($chain, [key => [childKey=>[...]]], founder, key, childKey, returnArrayOrString]
     *          1) $o true => [arr1, arr2,...]
     *          2) $o false => [value1, value2,...]
     * @param array $chain
     * @param array $arr
     * @param $n $needle
     * @param $v the key in array
     * @param $c the child key in array
     * @param bool $o is return specific values by key or full array
     * @return bool
     */
    public static function pather(array &$chain, array $arr, $n, $v, $c, $o = true)
    {
        foreach($arr as $a) {
            $chain[] = $o ? $a[$v] : $a;
            if ($a[$v] == $n) return true;
            if (is_array($a[$c]) && self::pather($chain, $a[$c], $n, $v, $c, $o)) return true;
            array_pop($chain);
        }
        return false;
    }

    /**
     * Various types according to the PHP variables to generate unique identification number
     *
     * @static
     * @param $mix
     * @param string|callable $func md5,crc32,sha1,...
     * @return mixed
     */
    public static function toGuidString($mix, $func = 'md5')
    {
        if (is_object($mix) && function_exists('spl_object_hash')) {
            return spl_object_hash($mix);
        } elseif (is_resource($mix)) {
            $mix = get_resource_type($mix) . strval($mix);
        } else {
            $mix = serialize($mix);
        }
        return call_user_func($func, $mix);
    }

    /**
     * A random string
     *
     * @static
     * @param $len
     * @param string|array $str1 The generated string or array
     * @param string|array $str2 The generated string or array
     * @return string
     */
    public static function random($len = 6, $str1 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz', $str2 = '')
    {
        mt_srand();//fixed: pcntl
        $hash = '';
        $max = (is_array($str1) ? count($str1) : strlen($str1)) - 1;
        $max2 = (is_array($str2) ? count($str2) : strlen($str2)) - 1;
        for ($i = 0; $i < $len; $i++) {
            if ($max > 0) $hash .= $str1[mt_rand(0, $max)];
            if (strlen($hash) === $len) break;
            if ($max2 > 0) $hash .= $str2[mt_rand(0, $max2)];
            if (strlen($hash) === $len) break;
        }
        return $hash;
    }

    /**
     * A random digital
     *
     * @static
     * @param $len
     * @return string
     */
    public static function randomDigital($len = 6)
    {
        return self::random($len, '0123456789');
    }


    /**
     * @param int $len - length of random string
     * @return string
     */
    public static function randomReadableString($len = 6)
    {
        return self::random($len, "bcdfghjklmnprstvwxyz", 'aeiou');
    }

    /**
     * @see GUID
     * @return string
     */
    public static function guid16()
    {
        return self::guid('0123456789abcdef');
    }

    /**
     * Replacing a few position of characters
     *
     * @param $str
     * @param array $indexes
     * @param string $mask
     * @return mixed
     */
    public static function mask($str, array $indexes, $mask = '*')
    {
        foreach($indexes as $index) {
            $str{$index} = $mask;
        }
        return $str;
    }

    /**
     * Globally Unique Identifier,like "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
     *
     * @param string $chars
     * @return string
     */
    public static function guid($chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz')
    {
        return self::mask(self::random(32, $chars), [8, 13, 18, 23], '-');
    }

    /**
     * Get Scale random
     *
     * @param array $scales [value:percent,...] ex. ['a':10.5,'b':9.5,'c':80]
     * @param array $weight [value:percent,...]
     * @return array
     */
    public static function getScaleRandom(array $scales, array $weight = [])
    {
        $ps = [];
        $p = 0;
        foreach($scales as $k => $v) {
            if (isset($weight[$k])) {
                $v = $v * (1 + $weight[$k]/100);
            }
            $v = strval($v);
            $d = self::getFloatLength($v);
            $p = $p > $d ? $p : $d;
            $ps[$v][] = $k;
        }

        if ($p) {
            foreach($ps as $k => $v) {
                $ps[strval($k * pow(10, $p))] = $v;
                unset($ps[$k]);
            }
        }

        $m = 0;
        $as = [];
        foreach($ps as $k => $v) {
            $m += $k * count($v);
            $as[$m] = $v;
        }

        mt_srand();//fixed: pcntl

        $r = mt_rand(1, $m);
        $t = 0;
        foreach($as as $k => $v) {
            if ($r > $t && $r <= $k) {
                return $v[array_rand($v)];
            } else {
                $t = $k;
            }
        }
    }

    /**
     * To smash an integer into, sum of multiple integers
     *
     * @param $total
     * @param $num
     * @param null $func [total, num, cost, index, average]
     * @param bool $shuffle
     * @return array
     */
    public static function smash($total, $num, $func = NULL, $shuffle = true)
    {
        is_callable($func) || $func = function ($total, $num, $cost, $i, $avg) {
            return floor($avg);
        };

        $collection = [];
        $cost = 0;
        $avg = $total / $num;//Average

        for ($i = 1; $i < $num; $i++) {
            $c = (int)call_user_func($func, $total, $num, $cost, $i, $avg);
            $collection[] = $c;
            $cost += $c;
        }

        $collection[] = $total - $cost;

        if ($shuffle) shuffle($collection);

        return $collection;
    }

    /**
     * Consistent Hash algorithm
     *
     * 0 ~ (2^32-1) ~ [0]
     * @param $key
     * @param array $nodes like: ['192.168.1.1', '192.168.1.2', '192.168.1.3']
     * @param int $replicas
     * @return string
     */
    public static function hash($key, $nodes = [], $replicas = 32)
    {
        $positions = [];

        is_array($nodes) || $nodes = [$nodes];

        foreach($nodes as $node) {
            for ($i = 0; $i < $replicas; $i++) {//hash the node into multiple positions
                $positions[crc32($node . '#' . $i)] = $node;
            }
        }

        if (empty($positions)) return NULL;

        ksort($positions, SORT_REGULAR);

        $_position = crc32($key);//hash key to a position

        //search values above the $_position
        foreach ($positions as $position => $node) {
            // start collecting node after passing key position
            if ($position > $_position) return $node;
        }

        return array_pop($positions);
    }

    //ASCII:[0-9][48-57],[A-Z]:[65-90],[a-z]:[97-122]

    /**
     * To convert the decimal to 62 decimal
     * String order: 0123456789ABCDEFGHIGKLMNOPQRSTUVWXYZabcdefghigklmnopqrstuvwxyz
     *
     * @param int $number
     * @return string
     */
    public static function decst($number)
    {
        $str = '';
        while ($number != 0) {
            $tmp = $number % 62;
            if ($tmp >= 10 && $tmp < 36) {
                $str .= chr($tmp + 55);
            } elseif ($tmp >= 36 && $tmp < 62) {
                $str .= chr($tmp + 61);
            } else {
                $str .= $tmp;
            }
            $number = intval($number / 62);
        }
        return strval(strrev($str));
    }

    /**
     * The 62 decimal converted to decimal
     * @param string $str
     * @return number
     */
    public static function stdec($str)
    {
        $number = 0;
        $len = strlen($str);
        $pos = 0;
        while($len--) {
            $tmp = $str{$pos++};
            $ord = ord($tmp);
            if ($ord >= 48 && $ord <= 57) {
                $number += $tmp * pow(62, $len);
            } elseif ($ord >= 65 && $ord <= 90) {
                $number+= ($ord - 55) * pow(62, $len);
            } else {
                $number+= ($ord - 61) * pow(62, $len);
            }
        }
        return $number;
    }

    /**
     * Gets a prefixed unique identifier based on the current time in microseconds.
     *
     * @param $prefix
     * @return string
     */
    public static function uniqId($prefix = '')
    {
        list($usec, $sec) = explode(" ", microtime());
        return sprintf("%s%05s%04s", $prefix, self::decst($sec), self::decst($usec*10000000));
    }

    /**
     * Hash string
     *
     * @param $str
     * @param $decst
     * @return string
     */
    public static function hashStr($str, $decst = true)
    {
        $hash = sprintf("%u", crc32($str));
        return $decst ? sprintf('%06s', self::decst($hash)) : $hash;
    }

    /**
     * Hash array
     *
     * @return string
     */
    public static function hashArr()
    {
        $arr = func_get_args();
        sort($arr, SORT_STRING); // use SORT_STRING rule
        return sha1(implode($arr));
    }
}