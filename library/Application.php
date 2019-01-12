<?php
/**
 * Application bootstrap
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
 * @version $Id: Application.php 8915 2017-11-05 03:38:45Z alacner $
 */

final class Tii_Application
{
    private $error;/** @var $error Exception */

    /**
     * @return Tii_Application
     */
    public static function getInstance()
    {
        static $instance;
        $instance || $instance = new self;
        return $instance;
    }

    /**
     * @static
     */
    public static function run()
    {
        if (Tii::get('tii.timezone')) {
            Tii_Time::timezone(Tii::get('tii.timezone'));
        }

        Tii_Event::init();

        //to execute ...
        try {
            self::getInstance()->execute();
            self::getInstance()->getProcessor()->over();
        } catch (Exception $e) {
            self::getInstance()->setError($e);
            try {//current error controller
                self::getInstance()->execute('error', 'error');
                self::getInstance()->getProcessor()->over();
            } catch (Exception $e1) {
                try {//default error controller
                    self::getInstance()->execute('error', 'error', 'default');
                    self::getInstance()->getProcessor()->over();
                } catch (Exception $e2) {
                    trigger_error($e2->getMessage(), E_USER_ERROR);
                }
            }
        }
    }

    /**
     * @return $this
     */
    public function getDispatcher()
    {
        return $this;
    }

    /**
     * Get/Destroy processor object
     *
     * @param bool $destroy destroy static object
     * @return Tii_Application_Processor_Abstract|bool
     */
    public function getProcessor($destroy = false)
    {
        static $processor;/** @var $processor Tii_Application_Processor_Abstract */
        if ($destroy) {
            $processor = null;
            return false;
        }
        if ($processor) return $processor;

        $processor = TII_PROCESSOR;
        $processor = new $processor;
        $processor = Tii_Event::filter('tii.application.processor', $processor);

        return $processor;
    }

    /**
     * @param $module
     * @param $controller
     * @return Tii_Application_Controller_Abstract
     * @throws Tii_Application_Exception
     */
    protected function loadControllerInstance($module, $controller)
    {
        $controllerFile = Tii::filename('controllers', $module, $controller, 'php');

        if (!is_file($controllerFile)) {
            throw new Tii_Application_Exception("controller file `%s' not exist", $controllerFile);
        }

        try {
            require_once $controllerFile;
            return Tii::object(str_replace('-', '_', sprintf('%s_%sController', $module, $controller)));
        } catch (Exception $e) {
            throw new Tii_Application_Exception("load controller `%s.%s` failed", $module, $controller);
        }
    }

    public function execute($action = NULL, $controller = NULL, $module = NULL)
    {
        $processor = $this->getProcessor();

        $module || $module = $processor->getModuleName();
        $controller || $controller = $processor->getControllerName();
        $action || $action = $processor->getActionName();

        //Set router
        $controllerInstance = $this->loadControllerInstance($module, $controller);
        /** @var $controllerInstance Tii_Application_Controller_Abstract */

        $actionMethod = sprintf('%sAction', str_replace('-', '_', $action));
        if (!method_exists($controllerInstance, $actionMethod)) {
            throw new Tii_Application_Exception("action `%s.%s.%s' not exist", $module, $controller, $action);
        }

        $controllerInstance->setModuleName($module);
        $controllerInstance->setControllerName($controller);
        $controllerInstance->setActionName($action);

        try {
            foreach(['init', $actionMethod, 'over'] as $step) {
                if ($controllerInstance->isInterrupt()) break;
                call_user_func([$controllerInstance, $step]);
            }
        } catch (Tii_Application_IgnoreException $e) {
            //ignore
        } catch (Exception $e) {
            call_user_func([$controllerInstance, 'error'], $e);
        }
    }

    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return Exception
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Magic methods
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (is_callable([$this->getProcessor(), $name])) {
            return call_user_func_array([$this->getProcessor(), $name], $arguments);
        }
    }
}