<?php
/**
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
 * @version $Id: sync.php 8915 2017-11-05 03:38:45Z alacner $
 */

require_once __DIR__ . '/../../Bootstrap.php';
Tii_Config::setDir(__DIR__.'/../');

$file = $argv[1];
$filename = basename($file);
if (empty($filename)) die("filename not found\n");

$files = Tii_Filesystem::getFiles(realpath(__DIR__.'/../../../../'), ["php"]);
foreach($files as $f) {
	if (preg_match('|'.preg_quote($filename).'$|', $f)) {
		echo "cp to $f ...";
		echo Tii_Filesystem::copy($file, $f) ? 'yes' : 'no';
		echo "\n";
	}
}
echo "done\n";