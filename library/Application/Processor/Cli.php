<?php
/**
 *  Processor cli
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
 * @version $Id: Cli.php 9139 2019-01-12 03:51:43Z alacner $
 */

class Tii_Application_Processor_Cli extends Tii_Application_Processor_Abstract
{
    private $env = [];
    protected $macPattern = "|([0-9a-f][0-9a-f][:-]){5}[0-9a-f][0-9a-f]|i";
    protected $ipPattern = "|([0-9]{1,3}\.){3}[0-9]{1,3}|i";

    public function __construct()
    {
        parent::__construct();
        //for rewrite
        $rewrite = Tii::get('tii.application.rewrite.cli', []);
        if (count($rewrite) > 0) {
            //not support
        }
        $this->argvParser();
    }

    protected function doBusyError($loadctrl, $load)
    {
        echo Tii::get('tii.application.server.busy_error.message', 'Server too busy. Please try again later.');
        exit;
    }

    public function getIp()
    {
        return Tii_Network::getIp();
    }

    public function getMacAddr()
    {
        return Tii_Network::getMacAddr();
    }

    public function getRequests()
    {
        return array_merge($this->getPairs(), $this->getEnvs());
    }

    public function getRequest($name, $default = NULL)
    {
        return $this->getPair($name, $this->getEnv($name, $default));
    }

    public function getEnvs()
    {
        return $this->env;
    }

    public function getEnv($name, $default = NULL)
    {
        return Tii::valueInArray($this->env, $name, $default);
    }

    public function getPid()
    {
        $pid = posix_getpid();
        $pid || $pid = getmypid();
        return $pid;
    }

    /**
     * @return Tii_Application_Processor_Cli_Response
     */
    public function getResponse()
    {
        return Tii::object('Tii_Application_Processor_Cli_Response');
    }

    private function argvParser()
    {
        if (!array_key_exists('argv', $_SERVER)) {
            $this->getResponse()->displayHelp();
        }

        $argv = Tii::argvParser();

        $this->setPairs($argv->pairs);
        $this->env = $argv->env;

        $mca = [];

        for ($i = 0; $i < 3; $i++) {
            if (isset($argv->env[$i]) && $argv->env[$i]{0} != "-") {
                $mca[] = $argv->env[$i];
            } else {
                break;
            }
        }

        list($moduleName, $controllerName, $actionName) = array_pad($mca, 3, NULL);

        $this->setModuleName($moduleName);
        $this->setControllerName($controllerName);
        $this->setActionName($actionName);
    }

    public function over()
    {}
}