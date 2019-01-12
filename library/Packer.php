<?php
/**
 * Code Uglify Packer
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
 * @version $Id: Packer.php 8923 2017-11-19 11:49:34Z alacner $
 */
final class Tii_Packer
{
    private $directory;
    private $output;
    private $uglify = "";
    private $excludeVar = [];

    private $priorities = [];
    private $exclude = [];

    public function __construct($directory, $output, $rewrite = true)
    {
        if (!is_dir($directory)) {
            throw new Tii_Exception("input directory not exist");
        }

        if (is_file($output) && !$rewrite) {
            throw new Tii_Exception("output file `%s' not exist", $output);
        }

        if (!Tii_Filesystem::touch($output)) {
            throw new Tii_Exception("output file `%s' un-writable", $output);
        }

        $this->directory = $directory;
        $this->output = $output;
    }

    public function priority()
    {
        $args = func_get_args();
        $this->priorities = array_reverse($args);
        return $this;
    }

    public function setUglify($uglify)
    {
        $this->uglify = $uglify;
    }

    public function excludeVar()
    {
        $this->excludeVar = array_merge($this->excludeVar, func_get_args());
    }

    /**
     * Except packer files.
     */
    public function exclude()
    {
        $exclude = [[], []];
        foreach(func_get_args() as $e) {
            if (strpos($e, '*') === false) {
                $exclude[0][] = $e;
            } else {
                $exclude[1][] = '|^'.str_replace('\*', '.*', preg_quote($e, '|')).'|i';
            }
        }
        $this->exclude = $exclude;
    }

    private function isExclude($file)
    {
        if (in_array($file, $this->exclude[0])) return true;

        foreach($this->exclude[1] as $preg) {
            if (preg_match($preg, $file)) {
                return true;
            }
        }

        return false;
    }

    public function execute($printTrace = false, $func = NULL, $compiledContents = [])
    {
        $files = new SplPriorityQueue();
        foreach(Tii_Filesystem::getRelativePathFiles($this->directory, ['php']) as $file) {
            if ($this->isExclude($file)) continue;
            $priority = array_search($file, $this->priorities);
            $files->insert($file, ($priority === false) ? -1 : $priority);
        }

        $files->setExtractFlags(SplPriorityQueue::EXTR_DATA);
        $files->top();

        while($files->valid()){
            $file = $files->current();
            $printTrace && print_r("$file\n");

            $compiledContents[] = call_user_func_array(
                is_callable($func) ? $func : 'self::compilePHPFile',
                [Tii_Filesystem::concat($this->directory, $file), $this->uglify, $this->excludeVar]
            );

            $files->next();
        }

        $compiledContents = implode("\n", $compiledContents);
        $compiledContents = str_replace("?>\n<?php", "\n", $compiledContents);
        if ($this->uglify) $compiledContents = str_replace("\n\n", '', $compiledContents);

        file_put_contents($this->output, $compiledContents, LOCK_EX);

        $printTrace && print("".$this->output."\n");
        return true;
    }

    /**
     * Complie a php file
     *
     * @param $filename
     * @param string|null $uglify
     * @param array $pv exclude
     * @return string
     */
    public static function compilePHPFile($filename, $uglify = NULL, $pv = [])
    {
        $content = file_get_contents($filename);
        $stripStr = '';
        $tokens =   token_get_all($content);
        $last_space = false;
        $tag_number = 0;
        for ($i = 0, $j = count ($tokens); $i < $j; $i++) {
            if (is_string ($tokens[$i])) {
                $last_space = false;
                $stripStr .= $tokens[$i];
            } else {
                switch ($tokens[$i][0]) {
                    case T_OPEN_TAG:
                        $tag_number++;
                        $stripStr .= $tokens[$i][1];
                        break;
                    case T_CLOSE_TAG:
                        $tag_number--;
                        $stripStr .= $tokens[$i][1];
                        break;
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        break;
                    case T_WHITESPACE:
                        if (!$last_space) {
                            $stripStr .= ' ';
                            $last_space = true;
                        }
                        break;
                    default:
                        $last_space = false;
                        $stripStr .= $tokens[$i][1];
                }
            }
        }

        $tag_number && $stripStr .= "\n?>";

        if (empty($uglify)) return $stripStr;

        //uglify
        $tokens = token_get_all($stripStr);

        $stripStr = '';
        $pv = array_merge([//var
            '$this',
            '$GLOBALS', '$_SERVER', '$_GET', '$_POST', '$_FILES', '$_REQUEST', '$_SESSION', '$HTTP_POST_VARS', '$HTTP_GET_VARS', '$HTTP_SESSION_VARS', '$HTTP_ENV_VARS', '$HTTP_COOKIE_VARS', '$HTTP_POST_FILES', '$HTTP_SERVER_VARS', '$HTTP_RAW_POST_DATA', '$_ENV', '$_COOKIE',
            '$php_errormsg', '$http_response_header', '$argc', '$argv',
        ], $pv);
        $f = 0;//func nested
        $fi = false;
        $b = 0;//brace holder
        $n = 0;//local self-increasing
        $lv = [];//local var
        $funcStr = '';
        for ($i = 0, $j = count($tokens); $i < $j; $i++) {
            if (is_string ($tokens[$i])) {
                if ($f > 0) {
                    $funcStr .= $tokens[$i];
                    switch($tokens[$i]) {
                        case '{':
                            $fi = true;
                            $b++;
                            break;
                        case '}':
                            $b--;
                            break;
                        default:
                    }

                    if ($fi && $b === 0) {
                        $stripStr .= $funcStr;
                        $n = 0;
                        $lv = [];
                        $funcStr = "";
                        $fi = false;
                        $f = 0;
                    }
                } else {
                    $stripStr .= $tokens[$i];
                }
            } else {
                switch ($tokens[$i][0]) {
                    case T_CURLY_OPEN:
                        $b++;
                        if ($f > 0) {
                            $funcStr .= $tokens[$i][1];
                        } else {
                            $stripStr .= $tokens[$i][1];
                        }
                        break;
                    case T_VARIABLE:
                        if ($f > 0) {
                            if (in_array($tokens[$i][1], $pv)) {
                                $funcStr .= $tokens[$i][1];
                            } else {
                                if (!isset($lv[$tokens[$i][1]])) {
                                    while(($name = '$'.$uglify.Tii_Math::decst($n++)) && in_array($name, $pv)) {
                                    }
                                    $lv[$tokens[$i][1]] = $name;
                                }
                                $funcStr .= $lv[$tokens[$i][1]];
                            }
                        } else {
                            $pv[] = $tokens[$i][1];
                            $stripStr .= $tokens[$i][1];
                        }
                        break;
                    case T_FUNCTION:
                        if (strtolower($tokens[$i-2][1]) == 'abstract' || strtolower($tokens[$i-4][1]) == 'abstract') {
                            $stripStr .= $tokens[$i][1];
                        } else {
                            $f++;
                            $funcStr .= $tokens[$i][1];
                        }
                        break;
                    default:
                        if ($f > 0) {
                            $funcStr .= $tokens[$i][1];
                        } else {
                            $stripStr .= $tokens[$i][1];
                        }
                }
            }
        }

        return $stripStr;
    }
}