<?php
/**
 * Processor Abstract
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

abstract class Tii_Application_Processor_Abstract
{
    private $interrupt = false;
    private $vars = [];

    private $moduleName;
    private $controllerName;
    private $actionName;

    public function __construct()
    {
        $this->busyError();

        $this->setModuleName($this->getDefaultModuleName());
        $this->setControllerName($this->getDefaultControllerName());
        $this->setActionName($this->getDefaultActionName());
    }

    protected function busyError()
    {
        $loadctrl = Tii::get('tii.application.server.busy_error.loadctrl', 0);
        if ($loadctrl && $load = sys_getloadavg()) {
            if ($load[0] > $loadctrl) $this->doBusyError($loadctrl, $load[0]);
        }
    }

    /**
     * @return bool
     */
    public function isInterrupt()
    {
        return $this->interrupt;
    }

    /**
     * interrupt processor
     */
    public function setInterrupt($interrupt = true)
    {
        $this->interrupt = $interrupt;
        return $this;
    }

    /**
     * Get processor name
     *
     * @see Tii_Application::getProcessor
     * @return string
     */
    public function getProcessorName()
    {
        $class = new ReflectionClass($this);
        return strtolower(substr(strrchr($class->getShortName(), "_"), 1));
    }

    public function getDefaultModuleName()
    {
        return Tii::get('tii.application.module');
    }

    public function getDefaultControllerName()
    {
        return Tii::get('tii.application.controller');
    }

    public function getDefaultActionName()
    {
        return Tii::get('tii.application.action');
    }

    public function setModuleName($name)
    {
        $this->moduleName = $name;
        return $this;
    }

    public function getModuleName()
    {
        return $this->moduleName ?: $this->getDefaultModuleName();
    }

    public function setControllerName($name)
    {
        $this->controllerName = $name;
        return $this;
    }

    public function getControllerName()
    {
        return $this->controllerName ?: $this->getDefaultControllerName();
    }

    public function setActionName($name)
    {
        $this->actionName = $name;
        return $this;
    }

    public function getActionName()
    {
        return $this->actionName ?: $this->getDefaultActionName();
    }

    /**
     * @return Tii_Application
     */
    public function getDispatcher()
    {
        return Tii_Application::getInstance();
    }

    public function getRequestTimeFloat()
    {
        return $_SERVER['REQUEST_TIME_FLOAT'] ?: Tii_Time::micro();
    }

    public function getRequestTime()
    {
        return $_SERVER['REQUEST_TIME'] ?: Tii_Time::now();
    }

    /**
     * Assign a variable
     *
     * @param $key
     * @param NULL $value
     * @param string $group
     * @return $this
     */
    public function setPair($key, $value = NULL, $group = '_')
    {
        $this->vars[$group][$key] = $value;
        return $this;
    }

    /**
     * Assign vars
     *
     * @param array $vars
     * @param string $group
     * @return $this
     */
    public function setPairs($vars, $group = '_')
    {
        $this->vars[$group] = $vars;
        return $this;
    }

    /**
     * Get a var
     *
     * @param $key
     * @param NULL $default
     * @param string $group
     * @return NULL
     */
    public function getPair($key, $default = NULL, $group = '_')
    {
        return isset($this->vars[$group][$key]) ? $this->vars[$group][$key] : $default;
    }

    /**
     * Get vars
     *
     * @param array $default
     * @param string $group
     * @return mixed
     */
    public function getPairs($default = [], $group = '_')
    {
        return isset($this->vars[$group]) ? $this->vars[$group] : $default;
    }

    /**
     * Contorller internal adjustment, will not affect the page redirects
     *
     * @param string $action
     * @param string $controller
     * @param string $module
     * @return $this
     */
    public function forward($action = NULL, $controller = NULL, $module = NULL)
    {
        $this->getDispatcher()->execute($action, $controller, $module);
        return $this;
    }

    /**
     * @see Tii_Config::lang
     * @return mixed
     */
    public function lang()
    {
        return call_user_func_array('Tii_Config::lang', func_get_args());
    }

    /**
     * Magic methods
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (preg_match('/get(.*)Helper/i', $name)) {//Call helper methods
            return call_user_func('Tii::' . $name);
        } else if (is_callable([$this->getResponse(), $name])) {
            return call_user_func_array([$this->getResponse(), $name], $arguments);
        }
    }

    abstract protected function doBusyError($loadctrl, $load);
    abstract public function over();
    abstract public function getRequest($name, $default = NULL);
    abstract public function getRequests();
    abstract public function getResponse();
}