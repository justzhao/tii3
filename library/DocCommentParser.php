<?php
/**
 * PHPDoc Comment Parser
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
 * keyword:
 * "access" "author" "copyright" "deprecated" "example" "ignore"
 * "internal" "link" "param" "return" "see" "since" "tutorial" "version"
 *
 * Simple example usage:
 * $a = new Tii_DocCommentParser($string);
 * $a->desc();
 * $a->shortDesc();
 * $a->author; //like $a->*; return [];
 * $a->isEnabled(); //like $a->is*(); // return a bool value
 * $a->intExpired(); //like $a->int*(); return a int value
 * $a->floatValue(); //like $a->float*(); return a float value
 * $a->getAuthor(); //like $a->get*(); return a value
 *
 * @author  Fitz Zhang <alacner@gmail.com>
 * @version $Id: DocCommentParser.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_DocCommentParser
{
    private $_shortDesc; /** @var string short description */
    private $_longDesc; /** @var string long description */
    private $_parameters = []; /** @var array parameters */

    /**
     * @param string $commentString  Comment String to parse
     */
    public function __construct($commentString)
    {
        //Get the comment
        if (!preg_match('|^/\*\*(.*)\*/|s', $commentString, $comment)) {
            return false;
        }

        $comment = trim($comment[1]);

        //Get all the lines and strip the * from the first character
        if (preg_match_all('|^\s*\*(.*)|m', $comment, $lines) === false) {
            return false;
        }
        $lines = explode("\n", trim(implode("\n", array_map(function($l){return trim($l);}, $lines[1]))));

        $longDesc = [];
        for($i = 0, $j = count($lines); $i < $j; $i++) {
            $line = $lines[$i];
            if (strpos($line, '@') === 0) {
                $param = substr($line, 1, strpos($line, ' ') - 1); //Get the parameter name
                $value = substr($line, strlen($param) + 2); //Get the value
                if (preg_match('|(.*)<<<(\w+)$|', $value, $eof)) {
                    $value = [];
                    $value[] = $eof[1];
                    do {
                        if ($eof[2].';' == $lines[++$i]) break;
                        else $value[] = $lines[$i];
                    } while($i < $j);
                    $value = implode("\n", $value);
                }
                if (isset($this->_parameters[$param])) {
                    $this->_parameters[$param][] = $value;
                } else {
                    $this->_parameters[$param] = [$value];
                }
            } else {

                if (isset($this->_shortDesc)) {
                    $longDesc[] = $line;
                } else {
                    $this->_shortDesc = $line;
                }
            }
        }

        $this->_longDesc = implode("\n", $longDesc);

        return true;
    }

    /**
     * Get the short description
     *
     * @return string The short description
     */
    public function shortDesc()
    {
        return $this->_shortDesc;
    }

    /**
     * Get the long description
     *
     * @return string The long description
     */
    public function desc()
    {
        return $this->_longDesc;
    }

    /**
     * Get all parameters
     *
     * @return array
     */
    public function get()
    {
        return [
            'shortDesc' => $this->shortDesc(),
            'desc' => $this->desc(),
            'parameters' => $this->_parameters,
        ];
    }

    /**
     * @param $name
     * @return NULL
     */
    public function __get($name)
    {
        return Tii::valueInArray($this->_parameters, $name);
    }

    public function __call($name, $arguments)
    {
        //get a value and cast it
        preg_match('/(is|int|float|get)(.*)/i', $name, $matches);
        if ($matches) {
            $key = lcfirst($matches[2]);
            $value = isset($this->_parameters[$key][0])
                ? $this->_parameters[$key][0]
                : (isset($arguments[0]) ? $arguments[0] : NULL);
            switch($matches[1]) {
                case 'is': return (bool) $value;
                case 'int': return intval($value);
                case 'float': return floatval($value);
                case 'get':
                default:
                    return $value;
            }
        }
    }
}