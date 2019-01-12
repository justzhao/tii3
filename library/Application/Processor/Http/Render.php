<?php
/**
 * Render class
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
 * @version $Id: Render.php 8915 2017-11-05 03:38:45Z alacner $
 */

class Tii_Application_Processor_Http_Render extends Tii_Application_Abstract
{
    /**
     * @param $__file_
     */
    public function includer($__file_)
    {
        extract((array)$this->getView());
        include $__file_;
    }

    /**
     * include layout
     *
     * @param string $layout
     * @param NULL $moduleName
     */
    public function layout($layout, $moduleName = NULL)
    {
        $this->includer(Tii::filename('views', $moduleName ?: $this->getModuleName(), $layout, 'phtml', "layouts"));
    }

    /**
     * include fragment
     *
     * @param string $fragment
     * @param NULL $moduleName
     */
    public function fragment($fragment, $moduleName = NULL)
    {
        $this->includer(Tii::filename('views', $moduleName ?: $this->getModuleName(), $fragment, 'phtml', "fragments"));
    }

    /**
     * include render
     *
     * @param string $render
     * @param NULL $controllerName
     * @param NULL $moduleName
     */
    public function render($render, $controllerName = NULL, $moduleName = NULL)
    {
        $moduleName || $moduleName = $this->getModuleName();
        $controllerName || $controllerName = $this->getControllerName();

        include Tii::filename('views', $moduleName, $render, 'phtml', "scripts/$controllerName");
    }

    /**
     * Output
     *
     * @param $expired
     * @return string
     */
    public function display($expired = 0)
    {
        $moduleName = $this->getModuleName();
        $controllerName = $this->getControllerName();

        $viewer = (array)$this->getView();

        $rendFile = Tii::filename('views', $moduleName, $this->getRender(), 'phtml', "scripts/$controllerName");

        $layout = $this->getLayout();

        $this->noLayout();
        $this->noRender();

        if (!$layout) {
            if (!$expired) return $this->renderFile($rendFile, $viewer);
            echo $response = $this->renderFile($rendFile, $viewer, true);
            return $this->cachingViewData($expired, $response);
        }

        $render = $this->renderFile($rendFile, $viewer, true);
        $viewer = array_merge($viewer, ['_viewer_' => $render]);

        if (($_moduleName = strstr($layout, '/', true)) !== false) {
            $moduleName = $_moduleName;
            $layout = substr($layout, strlen($_moduleName)+1);
        }
        unset($_moduleName);

        $layoutFile = Tii::filename('views', $moduleName, $layout, 'phtml', "layouts");
        if (!is_file($layoutFile)) {
            $layoutFile = Tii::filename('views', "default", $layout, 'phtml', "layouts");
        }

        if (!$expired) return $this->renderFile($layoutFile, $viewer);
        echo $response = $this->renderFile($layoutFile, $viewer, true);

        return $this->cachingViewData($expired, $response);
    }

    /**
     * render
     *
     * @param $__file_
     * @param array $__viewer_
     * @param bool $__return_
     * @return string
     * @throws Tii_Application_Exception
     */
    protected function renderFile($__file_, $__viewer_ = [], $__return_ = false)
    {
        if (empty($__file_) || !is_file($__file_)) {
            throw new Tii_Application_Exception("render file '%s` not exist", $__file_);
        }
        $_viewer = &$__viewer_;
        extract((array)$_viewer);
        if ($__return_) {
            ob_start();
            ob_implicit_flush(false);
            require $__file_;
            return ob_get_clean();
        }
        require $__file_;
    }
}