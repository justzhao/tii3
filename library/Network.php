<?php
/**
 * Network class
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
 * @version $Id: Network.php 8923 2017-11-19 11:49:34Z alacner $
 */

final class Tii_Network
{
    private static $macPattern = "|([0-9a-f][0-9a-f][:-]){5}[0-9a-f][0-9a-f]|i";
    private static $ipPattern = "|([0-9]{1,3}\.){3}[0-9]{1,3}|i";

    /**
     * Get Available Port
     *
     * @param int $start
     * @param int $end
     * @return mixed
     */
    public static function getAvailablePort($start = 10000, $end = 20000)
    {
        while(true) {
            foreach(range($start, $end) as $port) {
                if (!fsockopen('127.0.0.1', $port, $errno, $errstr, 1)) return $port;
            }
            sleep(1);//good luck!!!
        }
    }

    /**
     * WARNING: Use the exec function here
     *
     * @param string $preg
     * @param array $exclude
     * @param bool $single
     * @return array
     */
    private static function configParser($preg = "", $exclude = [], $single = false)
    {
        static $output;

        $output || @exec("ifconfig -a", $output);
        $output || @exec("ipconfig /all", $output);
        $output || $output = [];

        $arr = [];
        foreach($output as $value) {

            if (preg_match($preg, $value, $matches)) {
                if (in_array($matches[0], $exclude)) continue;

                if ($single) {
                    return $matches[0];
                } else {
                    $arr[] = $matches[0];
                }
            }
        }
        return $arr;
    }

    public static function getIp()
    {
        return self::configParser(self::$ipPattern, ["127.0.0.1"], true);
    }

    public static function getMacAddr()
    {
        return self::configParser(self::$macPattern, [], true);
    }

    public static function getMacAddrs()
    {
        return self::configParser(self::$macPattern);
    }

    /**
     * Parse hostname to host & port
     *
     * @param $host
     * @param int $port
     * @return array
     */
    public static function parseHost($host, $port = 22)
    {
        return array_pad(explode(':', $host), 2, $port);
    }

    /**
     * Get hostname port only
     *
     * @param $host
     * @param int $port
     * @return int
     */
    public static function getPort($host, $port = 22)
    {
        return preg_match('|:(\d+)$|', $host, $m) ? $m[1] : $port;
    }

    /**
     * Get network range
     * NOTICE: Only supports IPv4.
     *
     * Network range:
     *  single ip:1.2.3.4  -> 1.2.3.4-1.2.3.4
     *  wildcard: 1.2.3.*  -> 1.2.3.0-1.2.3.255
     *  IP segment: 1.2.3.0-1.2.3.255
     *
     * @param  string  $range Network range
     * @return array [lower, upper]
     */
    public static function parseIpRange($range)
    {
        if (strpos($range, '*') !== false) {//wildcard format
            $range = sprintf('%s-%s', str_replace('*', '0', $range), str_replace('*', '255', $range));
        }
        return (strpos($range, '-') !== false) ? explode('-', $range, 2) : [$range, $range];
    }

    /**
     * Check whether the IP is in the range of the specified network
     * NOTICE: Only supports IPv4.
     *
     * Network range:
     *    Wildcard: 1.2.3.*
     *    CIDR: 1.2.3.0/24 || 1.2.3.4/255.255.255.0
     *    IP segment: 1.2.3.0-1.2.3.255
     *
     * @param  string  $ip The IP address
     * @param  string  $range Network range
     * @return bool
     */
    public static function ipInRange($ip, $range)
    {
        if (strpos($range, '/') !== false) { // CIDR
            list($range, $netmask) = explode('/', $range, 2);

            if (strpos($netmask, '.') !== false) {

                $netmask = str_replace('*', '0', $netmask);
                $netmask_dec = ip2long($netmask);

                return ((ip2long($ip) & $netmask_dec) === (ip2long($range) & $netmask_dec));

            } else {

                $args = array_map(function($v){return empty($v) ? '0' : $v;}, array_pad(explode('.', $range), 4, 0));
                array_unshift($args, '%u.%u.%u.%u');
                $range_dec = ip2long(call_user_func_array('sprintf', $args));
                $ip_dec = ip2long($ip);

                $wildcard_dec = pow(2, (32 - $netmask)) - 1;
                $netmask_dec = ~ $wildcard_dec;

                return (($ip_dec & $netmask_dec) === ($range_dec & $netmask_dec));
            }

        } else {

            list($lower, $upper) = self::parseIpRange($range);

            $lower_dec = (float) sprintf('%u', ip2long($lower));
            $upper_dec = (float) sprintf('%u', ip2long($upper));
            $ip_dec = (float) sprintf('%u', ip2long($ip));
            return (($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec));
        }
    }

    /**
     * Check whether the IP is in the ranges of the specified network
     *
     * @param $ip
     * @param array $rules [range => deny_or_not[,...]] or [['range' => '1.2.3.*', 'allow' => ''][,...]]
     * @param bool $default
     * @return bool
     */
    public static function ipInRanges($ip, $rules = [], $default = true)
    {
        if (isset($rules[0])) {//list mode --> one by one, Compatible with java map.
            $_rules = [];
            foreach($rules as $rule) {
                $_rules[$rule['range']] = $rule['allow'];
            }
        } else {
            $_rules = $rules;
        }
        foreach($_rules as $range => $allow) {
            $_allow = self::ipInRange($ip, $range);
            if (!$_allow) continue;
            return $allow;
        }
        return $default;
    }
}