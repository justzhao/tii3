<?php
/**
 * Filter: tii.validators
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
 * @version $Id: tii.validators.php 8915 2017-11-05 03:38:45Z alacner $
 */

Tii_Event::register(basename(__FILE__, ".php"), function($validators)
{
	return array_merge($validators, [
		'range' => function($arr, $k, $min, $max) { $n = (int)$arr[$k];return ($n >= $min && $n <= $max); },
		'min' => function($arr, $k, $min) { $n = (int)$arr[$k];return ($n >= $min); },
		'max' => function($arr, $k, $max) { $n = (int)$arr[$k];return ($n <= $max); },
		'strlen_between' => function($arr, $k, $min, $max) { $n = strlen((string)$arr[$k]);return ($n >= $min && $n <= $max); },
		'strlen_min' => function($arr, $k, $min) { $n = strlen((string)$arr[$k]);return ($n >= $min); },
		'strlen_max' => function($arr, $k, $max) { $n = strlen((string)$arr[$k]);return ($n <= $max); },
	]);
});