<?php
/**
 * Assistant controller paging abstract classes
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
 * @version $Id: Abstract.php 8915 2017-11-05 03:38:45Z alacner $
 */

abstract class Tii_Application_Helper_Pager_Abstract extends Tii_Application_Abstract
{
    protected $options = [
        'num' => 0,//Need to the total number of the page
        'perpage' => 10,//Number of pages per page
        'curpage' => 1,//The current page number
        'mpurl' => '',//The first half page url
        'ext' => '',//Second part page url
        'page' => 10,//Each page shows a few pages
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
        $this->filter();
    }

    /**
     * 页面渲染抽象方法
     */
    abstract public function render();

    /**
     * 过滤器，处理一些页面的值，保存在options中
     */
    protected function filter()
    {
        if ($this->options['num'] > $this->options['perpage']) {
            $offset = 2;
            $pages = @ceil($this->options['num'] / $this->options['perpage']);

            if ($this->options['page'] > $pages) {
                $from = 1;
                $to = $pages;
            } else {
                $from = $this->options['curpage'] - $offset;
                $to = $from + $this->options['page'] - 1;
                if ($from < 1) {
                    $to = $this->options['curpage'] + 1 - $from;
                    $from = 1;
                    if ($to - $from < $this->options['page']) {
                        $to = $this->options['page'];
                    }
                } else if($to > $pages) {
                    $from = $pages - $this->options['page'] + 1;
                    $to = $pages;
                }
            }
            $this->options['offset'] = $offset;
            $this->options['pages'] = $pages;
            $this->options['from'] = $from;
            $this->options['to'] = $to;
        }
    }
}