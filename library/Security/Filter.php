<?php
/**
 * Security Filter Class
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
 * @version $Id: Filter.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_Security_Filter
{
    /**
     * javascript, CSS, iframes, object not safe parameters, such as high level
     * @param  string $value The value of the need to filter
     * @return string
     */
    public static function htmlTags($value)
    {
        $value = preg_replace("/(javascript:)?on(click|load|key|mouse|error|abort|move|unload|change|dblclick|move|reset|resize|submit)/i", "&111n\\2", $value);
        $value = preg_replace("/<script(.*?)>(.*?)<\/script>/si", "", $value);
        $value = preg_replace("/<iframe(.*?)>(.*?)<\/iframe>/si", "", $value);
        $value = preg_replace ("/<object.+<\/object>/iesU", '', $value);

        return $value;
    }

    /**
     * html special chars
     * @param  string $value The value of the need to filter
     * @return string
     */
    public static function htmlChars($value)
    {
        if (function_exists('htmlspecialchars')) return htmlspecialchars($value);
        return str_replace(["&", '"', "'", "<", ">"], ["&amp;", "&quot;", "&#039;", "&lt;", "&gt;"], $value);
    }

    /**
     * javascript value of dangerous characters
     * @param  string $value The value of the need to filter
     * @return string
     */
    public static function jsChars($value)
    {
        return str_replace(["\\", "'", "\"", "/", "\r", "\n"], ["\\x5C", "\\x27", "\\x22", "\\x2F", "\\x0D", "\\x0A"], $value);
    }

    /**
     * @param $string
     * @param bool $isurl
     * @return string
     */
    public static function str($string, $isurl = false)
    {
        $string = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '', $string);
        $string = str_replace(["\0","%00","\r"], '', $string);
        empty($isurl) && $string = preg_replace("/&(?!(#[0-9]+|[a-z]+);)/si", '&', $string);
        $string = str_replace(["%3C", '<'], '<', $string);
        $string = str_replace(["%3E",'>'], '>', $string);
        $string = str_replace(['"', "'", "\t", ' '], ['“','‘',' ',' '], $string);
        return trim($string);
    }
}