<?php
/**
 * Automatically using the [cache,monitor] with magic function.
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
 * When you call a nonexistent method, will automatically to call methodName with ReflectionClass cache config.
 * "@expired <int>" <method> default:0 permanent, -1 deleted, 1+ expired second
 * "@cacheName <string>" <method> default: guidString with $args, @see Tii::render
 * "@cacheMode <string>" <method> default: file
 * "@useExpired <bool>" <method> under cacheMode == 'file', default: false
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version $Id: Delegate.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_Delegate
{
    private $_;//pollutants

    /**
     * @param $that
     */
    public function __construct($that)
    {
        $clazz = new ReflectionClass($that);

        $annotations = [];
        //$cc = new Tii_DocCommentParser($clazz->getDocComment());
        foreach($clazz->getMethods() as $method) {
            $c = new Tii_DocCommentParser($method->getDocComment());

            if (!is_null($c->cacheMode)) {
                $annotations[$method->getName()]['cache'] = (object)[
                    'expired' => $c->intExpired(),
                    'cacheName' => $c->getCacheName(),
                    'cacheMode' => $c->getCacheMode(),
                    'useExpired' => $c->isUseExpired(true),
                ];
            }
        }

        $properties = [];
        foreach($clazz->getProperties() as $property) {
            $property->setAccessible(true);
            $properties[$property->name] = $property->getValue($that);
        }

        $this->_ = new stdClass();
        $this->_->that = $that;
        $this->_->clazz = $clazz;
        $this->_->namespace = Tii_Config::getIdentifier() . "." . $clazz->getName();
        $this->_->annotations = $annotations;
        $this->_->properties = $properties;
    }

    public function __get($name)
    {
        try {
            return $this->_->clazz->getProperty($name);
        } catch(Exception $e) {
            if (isset($this->_->annotations['__get']['cache'])) {//magic property cache
                return $this->_(
                    $name,
                    $this->_->annotations['__get']['cache'],
                    function($name, $that) {return $that->{$name};},
                    [$name, $this->_->that]
                );
            } else {
                return $this->_->that->{$name};
            }
        }
    }

    public function __call($name, $args)
    {
        if (isset($this->_->annotations[$name]['cache'])) {//normal cache
            return $this->_(
                $name,
                $this->_->annotations[$name]['cache'],
                [$this->_->that, $name],
                $args
            );
        } else if (isset($this->_->annotations['__call']['cache'])) {//magic function cache
            return $this->_(
                $name,
                $this->_->annotations['__call']['cache'],
                [$this->_->that, $name],
                $args
            );
        } else {
            return call_user_func_array([$this->_->that, $name], $args);
        }
    }

    private function _($name, $cfg, $function = NULL, array $param_arr)
    {
        if (empty($cfg->cacheName)) {
            $cacheName = $name . (empty($param_arr) ? "" : "." . Tii_Math::toGuidString($param_arr));
        } else {
            $cacheName = Tii::render($cfg->cacheName, array_merge($param_arr, $this->_->properties, ['__call' => $name]));
        }

        $key = $this->_->namespace . "." . $cacheName;

        switch($cfg->cacheMode) {
            case 'buffer':
                array_unshift($param_arr, $key, $function);
                return call_user_func_array('Tii::buffer', $param_arr);
            case 'cache':
                return Tii::object("Tii_Cache")->cached($key, $cfg->expired, $function, $param_arr);
            case 'file'://local file
            default:
                return Tii_Filesystem::cached($key, $cfg->expired, $function, $param_arr, $cfg->useExpired);
        }
    }
} 