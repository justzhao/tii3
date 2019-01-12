<?php
/**
 * Security encryption to decrypt, *** URL SAFE ***
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
 * @version $Id: Encryption.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_Security_Encryption
{
    private static $authcodeKey = NULL; //The default encryption and decryption key

    /**
     * Set the default encryption and decryption key
     * @static
     *
     */
    public static function setAuthcodeKey($authcodeKey = '')
    {
        self::$authcodeKey = $authcodeKey;
        return true;
    }
    /**
     * Get the default encryption and decryption key
     * @static
     *
     */
    private static function getAuthcodeKey()
    {
        self::$authcodeKey || self::setAuthcodeKey(Tii::get('tii.auth_code_key', Tii_Config::getIdentifier()));
        return self::$authcodeKey;
    }

    /**
     * auth code url safe
     *
     * @param $string
     * @param string $operation
     * @param string $key
     * @param int $expiry
     * @param string $keyc_hash
     * @return string
     */
    private static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0, $keyc_hash = '')
    {
        $ckey_length = 4;
        $key = md5($key != '' ? $key : self::getAuthcodeKey());
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5($keyc_hash), -$ckey_length)) : '';

        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? self::urlsafeBase64Decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = [];
        for ($i = 0; $i < 256; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if (((int)substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.self::urlsafeBase64Encode($result);
        }
    }

    /**
     * String encryption
     *
     * @static
     * @param string $data
     * @param int $expiry
     * @param string $key
     * @return string
     */
    public static function encode($data, $expiry = 0, $key = '')
    {
        return self::authcode($data, 'ENCODE', $key, $expiry, microtime());
    }

    /**
     * String encryption
     * WARNING: The result has been the same, may increase the risk of security
     * *** Deprecated ***
     *
     * @static
     * @param string $data
     * @param int $expiry
     * @param string $key
     * @return string
     */
    public static function encodeWithoutHash($data, $expiry = 0, $key = '')
    {
        return self::authcode($data, 'ENCODE', $key, $expiry, md5(md5(self::getAuthcodeKey())));
    }

    /**
     * Decrypt the string
     *
     * @static
     * @param $string
     * @param string $key
     * @return string
     */
    public static function decode($string, $key = '')
    {
        return self::authcode($string, 'DECODE', $key);
    }

    /**
     * url safe encode base64 with replace / + =
     *
     * @param $string
     * @return mixed
     */
    public static function urlsafeBase64Encode($string)
    {
        return str_replace(['+', '/', '='], ['-' ,'_', ''], base64_encode($string));
    }

    /**
     * url safe decode base64
     * @param $string
     * @return string
     */
    public static function urlsafeBase64Decode($string)
    {
        $data = str_replace(['-', '_'], ['+', '/'], $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
}