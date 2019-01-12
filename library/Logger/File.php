<?php
/**
 * Logging with file mode
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
 * @version $Id: File.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Logger_File extends Tii_Logger_Abstract
{
    private $path;
    private $format;

    public function __construct($path = NULL, $format = 'Ymd')
    {
        parent::__construct();
        $this->path = $path ?: Tii_Filesystem::getTempDir();
        Tii_Filesystem::mkdir($this->path);
        $this->format = $format;
    }

    public function doLog($message, $priority = Tii_Logger_Constant::ERR, $extras = NULL)
    {
        return file_put_contents(
            Tii_Filesystem::concat($this->path, Tii_Time::format($this->format) . '.log'),
            sprintf(
                "%s\t%s\t%s\t%s\n",
                Tii_Time::format('H:i:s'),
                $this->getPriorityName($priority),
                strval($message),
                json_encode($extras)
            ),
            FILE_APPEND
        );
    }
}