<?php
/**
 * Controller HTML form processing helper classes
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
 * @version $Id: Html.php 8923 2017-11-19 11:49:34Z alacner $
 */

class Tii_Application_Helper_Html extends Tii_Application_Abstract
{
    private $baseUrl = '';
    private $title = '';
    private $bodyAttrs = [];
    private $metas = []; //[{name => content},...]
    private $scripts = [];//place => {script|css => [{src|href?v=version => attr},...]}

    public function __construct()
    {
        $this->addMeta('generator', 'Tii/' . Tii_Version::VERSION);
        $this->baseUrl = Tii::get('tii.application.helper.html.base_url', '');
    }

    /**
     * Represents an entire HTML or XML document
     *
     * @return DOMDocument
     */
    public function dom()
    {
        return Tii::object('DOMDocument', '1.0', 'utf-8');
    }

    /**
     * Set title
     *
     * @param $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $default
     * @return string
     */
    public function title($default = '')
    {
        static $used; if ($used) return ''; $used = true;

        return $this->nodeBuilder('title', [], false, Tii_Event::filter(
            'tii.application.helper.html.title',
            $this->title ?: $default
        ));
    }

    /**
     * Add a meta tag or Adding multiple meta tag
     *
     * @param $name | metas [name => content]
     * @param $content
     * @return $this
     */
    public function addMeta($name, $content = NULL)
    {
        if (is_array($name)) {
            foreach($name as $k => $v) {
                $this->addMeta($k, Tii::value($v, $content));
            }
        } else {
            $this->metas[$name] = Tii::value($content, '');
        }

        return $this;
    }

    /**
     * Get Meta
     *
     * @param $name
     * @param $default
     * @return mixed
     */
    public function getMeta($name, $default = NULL)
    {
        return Tii::valueInArray($this->metas, $name, $default);
    }

    /**
     * Set Keywords
     *
     * @param $keywords
     * @return $this
     */
    public function setKeywords($keywords)
    {
        $this->addMeta('keywords', is_array($keywords) ? implode(',', $keywords) : $keywords);
        return $this;
    }

    /**
     * Get keywords
     *
     * @return string
     */
    public function getKeywords()
    {
        return $this->getMeta('keywords', '');
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->addMeta('description', $description);
        return $this;
    }

    /**
     * Get keywords
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->getMeta('description', '');
    }

    /**
     * @return string
     */
    public function metas()
    {
        static $used; if ($used) return ''; $used = true;

        $html = [];
        foreach(Tii_Event::filter('tii.application.helper.html.metas', $this->metas) as $name => $content) {
            $html[] = $this->nodeBuilder('meta', [
                'name' => $name,
                'content' => $content,
            ]);
        }
        return implode("\n", $html);
    }

    /**
     * Set Body attr
     *
     * @param $name
     * @param null $value
     * @return $this
     */
    public function setBodyAttr($name, $value = NULL)
    {
        if (is_array($name)) {
            foreach($name as $k => $v) {
                $this->setBodyAttr($k, Tii::value($v, $value));
            }
        } else {
            $this->bodyAttrs[$name] = $value;
        }

        return $this;
    }

    /**
     * Body wrapper
     *
     * @param $html
     * @param bool $closed not close html tag for <body>
     * @param $attrs
     * @return string
     */
    public function body($html, $closed = true, $attrs = [])
    {
        return $this->nodeBuilder('body', array_merge($attrs, $this->bodyAttrs), false, $html, $closed);
    }

    /**
     * Add a script|css node
     *
     * @param string $src
     * @param $ver
     * @param $attr
     * @param $place
     * @param $css
     * @return $this
     * @throws Tii_Application_Exception
     */
    private function addScript($src, $ver = NULL, $attr = NULL, $place = NULL, $css = false)
    {
        $parseUrl = parse_url($src);
        if (empty($parseUrl)) throw new Tii_Application_Exception("url `%s' was invalid", $src);

        is_array($attr) || $attr = [];
        if ($this->baseUrl && empty($parseUrl['host'])) {
            $src = ($src{0} === '#') ? substr($src, 1) : Tii_Http::concat($this->baseUrl, $src);
        }
        $attr[$css ? 'href' : 'src'] = Tii_Http::urlAppend($src, ['v' => Tii::value($ver, Tii_Version::VERSION)]);
        $this->scripts[$place][$css ? 'css' : 'script'][$src] = $attr;
        return $this;
    }

    /**
     * Adding multiple script file
     *
     * @param array $scripts
     * @param $ver
     * @param $attr
     * @param string $place
     * @param bool $css
     * @return $this
     */
    public function addScripts(array $scripts, $ver = NULL, $attr = NULL, $place = NULL, $css = false)
    {
        foreach($scripts as $src => $_attr) {
            if (is_numeric($src)) {
                $this->addScript($_attr, $ver, $attr, $place, $css);
            } else {
                is_array($_attr) || $_attr = ['ver' => $_attr];
                $this->addScript($src,
                    Tii::valueInArray($_attr, 'ver', $ver),
                    Tii::valueInArray($_attr, 'attr', $attr),
                    $place,
                    $css
                );
            }
        }
        return $this;
    }

    /**
     * @param $tag
     * @param $attrs
     * @param bool $close
     * @param null $content
     * @param $closed
     * @return string
     */
    public function nodeBuilder($tag, $attrs, $close = true, $content = NULL, $closed = true)
    {
        $_attrs = [];
        foreach($attrs as $name => $value) {
            $_attrs[] = sprintf('%s="%s"', $name, str_replace('"', '\"', $value));
        }
        $_attrs = $_attrs ? " " .implode(" ", $_attrs) : "";

        if ($close) {
            return sprintf('<%s%s />', $tag, $_attrs);
        } else {
            $html = [];
            $html[] = sprintf('<%s%s>', $tag, $_attrs);
            $html[] = Tii::value($content, "");
            if ($closed) $html[] = sprintf('</%s>', $tag);
            return implode('', $html);
        }
    }

    public function getScripts($place = NULL, $attr = [], $tag = 'script', $css = false)
    {
        $html = [];
        foreach(Tii::valueInArray($this->scripts[$place], $css ? 'css' : 'script', []) as $src => $_attr) {
            $html[] = $this->nodeBuilder($tag, array_merge($_attr, $attr), $css);
        }
        return implode("\n", $html);
    }

    public function getCsses($place = NULL)
    {
        return $this->getScripts($place, [
            'rel' => "stylesheet",
            'type' => "text/css",
        ], 'link', true);
    }

    /**
     * Append block html
     */
    public function block($place = NULL, $block)
    {
        Tii_Event::register('tii.application.helper.html.'.$place, function($html) use ($block) {
                return $html . "\n" . $block;
            });
    }

    public function __call($name, $arguments)
    {
        if (preg_match('#^add(.*)(Script|Css)(s|es)?$#iUs', $name, $m)) {
            $arguments = array_pad($arguments, 3, NULL);//$scripts|$src, $ver = NULL, $attr = NULL
            $arguments[] = $m[1];//$place
            $arguments[] = strtolower($m[2]) == 'css';//$css
            return call_user_func_array([$this, 'addScript' . (isset($m[3]) ? 's' : '')], $arguments);
        } else if (preg_match('#^get(.*)(Script|Css)(s|es)?$#iUs', $name, $m)) {
            switch(strtolower($m[2])) {
                case 'css':
                    return call_user_func_array([$this, 'getCsses'], [$m[1]]);
                    break;
                case 'script':
                    default:
                return call_user_func_array([$this, 'getScripts'], [$m[1]]);
            }
        } else if (preg_match('#^get(.*)$#iUs', $name, $m)) {
            $html = [];
            $place = strtolower($m[1]);
            if ($place == 'header') {
                $place = '';
                $html[] = $this->title();
                $html[] = $this->metas();
            }
            foreach(['Csses', 'Scripts']  as $type) {
                $append = call_user_func_array([$this, sprintf('get%s%s', ucfirst($place), $type)], []);
                if ($append) $html[] = $append;
            }
            return Tii_Event::filter('tii.application.helper.html.'.strtolower($m[1]), implode("\n", $html), $this);
        } else {
            array_unshift($arguments, $name);
            return call_user_func_array([$this, 'block'], $arguments);
        }
    }

    /**
     * create inputs
     *
     * @param $inputs [input1,input2,input,...]
     * @param $prepend scalar or function with input
     * @param $append scalar or function with input
     * @return string
     */
    public function inputs($inputs, $prepend = NULL, $append = NULL)
    {
        static $func;
        $func || $func = function($mixed, $input){
            if (is_callable($mixed)) return call_user_func($mixed, $input);
            else if (is_scalar($mixed)) return $mixed;
            else return serialize($mixed);//Should not appear this kind of
        };

        $html = [];
        foreach($inputs as $input) {
            if ($prepend)  $html[] = call_user_func_array($func, [$prepend, $input]);
            $html[] = $this->input($input);
            if ($append)  $html[] = call_user_func_array($func, [$append, $input]);
        }
        return implode("\n", $html);
    }

    /**
     * create input(if no id is passed, name will assumed as id)
     * this method can accept two type of parameter : 1-array 2-string
     * array: attributes => value:
     * <code>
     * $param = [];
     * $param["type"] = "text";
     * $param["name"] = "username";
     * $form->input($param);
     * </code>
     * string: type, name, value, checked, id, class
     * example:
     * <code>
     * $form->input("text", "input_name", "input_value");
     * </code>
     */
    public function input()
    {
        if (func_num_args() == 0) return false;
        $args = func_get_args();

        if (is_array($args[0])) {
            $inputArray = $args[0];
        } else {//extract the args...
            $inputArray = Tii::combiner($args, "type", "name", "value", "checked", "id", "class", "onchange");
        }

        if (!is_bool($inputArray['checked']) && !in_array($inputArray['type'], ['text', 'password', 'hidden'])) {
            $inputArray['checked'] = strval($inputArray['value']) === strval($inputArray['checked']);
        }

        $nodeInput = $this->dom()->createElement("input");

        foreach ($inputArray as $key => $value) {
            if ($key == "statement") continue;
            if ($key == "checked" && !$value) continue;
            $nodeInput->setAttribute($key, $value);
        }

        return $this->dom()->saveXML($nodeInput);
    }


     /**
     * Create select(if no id is passed, name will assumed as id)
     * this method can accept two type of parameter : 1-one array 2-lots parameters
      *
     * array: attributes => value: * <code>
     * $param = [];
     * $param["name"] = "test_select_name";
     * $param["id"] = "test_select_id";
     * $param["onchange"] = "alert(this.options[selectedIndex].text);";
     * $param["options"] = ["1"=>"Test1", "2"=>"Test2"];
     * $param["class"] = "someClass";
     * $form->select($param);
     * </code>
     * string: name, (array)options [,selected, id]
     * example:
     * <code>
     * $form->select("select_name", ["1"=>"Test1", "2"=>"Test2"]);
     * </code>
     */
    public function select()
    {
        if (func_num_args() == 0) return false;
        $args = func_get_args();

        if (is_array($args[0])) {
            $selectArray = $args[0];
        } else {//extract the args...
            $selectArray = Tii::combiner($args, "name", "options", "selected", "id", "class", "onchange");
        }

        $nodeSelect = $this->dom()->createElement("select");

        foreach($selectArray as $k => $v) {
            if ($k == "selected" || $k == "options") continue;
            $nodeSelect->setAttribute($k, $v);
        }

        $selected = Tii::valueInArray($selectArray, 'selected', '');
        if (is_array($selected)) $nodeSelect->setAttribute('multiple', 'multiple');

        $options = (array)$selectArray['options'];

        // build options
        foreach ($options as $key => $value) {
            $optionNode = $this->dom()->createElement("option");
            $optionNode->setAttribute("value", $key);

            if (strval($key) === strval($selected) || (is_array($selected) && in_array($key, $selected))) {
                $optionNode->setAttribute("selected", true);
            }
            $textNode = $this->dom()->createTextNode(Tii::lang($value));
            $optionNode->appendChild($textNode);

            $nodeSelect->appendChild($optionNode);
        }

        return $this->dom()->saveXML($nodeSelect);
    }
}