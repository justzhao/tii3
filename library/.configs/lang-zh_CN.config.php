<?php
/**
 * Tii Configure for I18N (中文) support
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
 * @version $Id: lang-zh_CN.config.php 8915 2017-11-05 03:38:45Z alacner $
 */

return [
	"invalid parameter" => "无效的参数",
	"validator `%s' not found" => "验证器`%s'缺失",
	"{0} didn't pass the validator `%s'" => "{0}未通过验证器[%s]的验证",
	'validation data,rules must be an array' => '待验证数据和验证规则都须为数组',
	'tii' => [
		'validator' => [
			'messages' => [
				'required' => '{0}必填',
				'not_empty' => '{0}不能为空',
				'regex' => "{0}{1.3}",
				'equals' => "{0}须等于`{1.2}'",
				'date' => '{0}非有效的时间',
			],
			'alias' => [
				//'name1' => '别名',
			],
		],
	],
	"class `%s' not found" => "类[%s]加载失败",
	"include directory `%s' not exist" => "不存在加载的目录：`%s'",
	"parameter `%s' not exist" => "参数`%s'不存在",
	'invalid session save handler' => '无效的Session句柄',
	'CSRF security error' => "验证跨站请求伪造(Cross Site Request Forgery)失败，请重试！",
	//给 Tii_Application_Helper_Html:select 的 value 替换
	"controller file `%s' not exist" => "没找到控制器文件：`%s'",
	"load controller `%s.%s` failed" => "加载控制器[%s.%s]失败",
	"action `%s.%s.%s' not exist" => "处理器[%s.%s.%s]不存在",
	"effective cache in chain %s not found" => "缓存链%s中不存在有效的句柄",
	"url `%s' was invalid" => "错误的链接：%s",
	's' => '秒',
	'ms' => '毫秒',
];