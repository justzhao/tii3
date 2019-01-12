<?php
/**
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
 * @version $Id: app.php 8915 2017-11-05 03:38:45Z alacner $
 */
require_once __DIR__ . '/../../Bootstrap.php';

$appName = $argv[1] ?: 'tii'.Tii_Time::format('YmdHis');
$output = $argv[2] ?: sys_get_temp_dir();

if (!Tii_Filesystem::isWritable($output)) {
	throw new Tii_Exception("output directory `%s' un-writable", $output);
}

$tiifile = 'Tii-' . preg_replace('|^(\d+\.\d+)(.*)|i', '$1', Tii_Version::VERSION) .'.php';

$baseDir = Tii_Filesystem::concat($output, $appName);

$folders = [
	'application/default/controllers',
	'application/default/views/layouts',
	'application/default/views/scripts',
	'application/default/views/scripts/error',
	'application/task/controllers',
	'configs/local',
	'library',
	'public/static',
	'docs',
];
foreach($folders as $folder) {
	Tii_Filesystem::mkdir(Tii_Filesystem::concat($baseDir, $folder));
}

/** copies */

//config
Tii_Filesystem::copy(__DIR__ . '/../tii.config.php', Tii_Filesystem::concat($baseDir, 'configs', 'tii.config.php'));
//lang
Tii_Filesystem::touch(Tii_Filesystem::concat($baseDir, 'configs', 'lang-default.config.php'));
//robots
Tii_Filesystem::touch(Tii_Filesystem::concat($baseDir, 'public', 'robots.txt'));
//favicon
Tii_Filesystem::copy(__DIR__ . '/../resources/icon/favicon.ico', Tii_Filesystem::concat($baseDir, 'public', 'favicon.ico'));
//.htaccess
Tii_Filesystem::copy(__DIR__ . '/../.htaccess', Tii_Filesystem::concat($baseDir, 'public', '.htaccess'));

//.error
Tii_Filesystem::copy(__DIR__ . '/../resources/error.phtml', Tii_Filesystem::concat($baseDir, 'application/default/views/scripts/error', 'error.phtml'));
//.debug_mode error
Tii_Filesystem::copy(__DIR__ . '/../resources/error_debug_mode.phtml', Tii_Filesystem::concat($baseDir, 'application/default/views/scripts/error', 'error_debug_mode.phtml'));

$data = <<<eot
#!/usr/bin/env php
<?php
/**
 * shell entry
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version \$Id: app.php 8915 2017-11-05 03:38:45Z alacner $
 */

\$_directories = [
	dirname(__FILE__) . '/configs/local/configuration.php',// local first
	dirname(__FILE__) . '/configs/configuration.php',
];

foreach (\$_directories as \$_file) {
	if (is_file(\$_file)) {
		require_once \$_file;
		break;
	}
}
unset(\$_directories, \$_file);

if (!class_exists('Tii_Version')) {//check framework has already loaded
	trigger_error("The tii framework not loaded correctly", E_USER_ERROR);
}

Tii_Application::run();
eot;
file_put_contents(Tii_Filesystem::concat($baseDir, 'shell'), $data);


$data = <<<eot
<?php
/**
 * web entry
 *
 * @author Fitz Zhang <alacner@gmail.com>
 * @version \$Id: app.php 8915 2017-11-05 03:38:45Z alacner $
 */

\$_directories = [
	dirname(__FILE__) . '/../configs/local/configuration.php',// local first
	dirname(__FILE__) . '/../configs/configuration.php',
];

foreach (\$_directories as \$_file) {
	if (is_file(\$_file)) {
		require_once \$_file;
		break;
	}
}
unset(\$_directories, \$_file);

if (!class_exists('Tii_Version')) {//check framework has already loaded
	trigger_error("The tii framework not loaded correctly", E_USER_ERROR);
}

//session start
if (Tii::get('tii.application.session.start', false)) {
	Tii_Application_Session::start(Tii::get('tii.application.session.handler', NULL));
}

Tii_Application::run();
eot;
file_put_contents(Tii_Filesystem::concat($baseDir, 'public', 'index.php'), $data);

$data = <<<eot
<?php
require_once __DIR__ . '/../library/$tiifile';//Load the tii packaging library
Tii_Config::setDir(__DIR__);//Sets the path to the configuration file
eot;
file_put_contents(Tii_Filesystem::concat($baseDir, 'configs', 'configuration.php'), $data);

$data = <<<eot
<?php

class Default_ErrorController extends Tii_Application_Controller_ErrorAbstract
{
	public function errorAction()
	{
		if (Tii_Config::isDebugMode()) {
			\$this->setRender('error_debug_mode');
		}
	}
}
eot;
file_put_contents(Tii_Filesystem::concat($baseDir, 'application/default/controllers', 'error.php'), $data);


$data = <<<eot
<?php

class Default_IndexController extends Tii_Application_Controller_Abstract
{
	public function indexAction()
	{}
}
eot;
file_put_contents(Tii_Filesystem::concat($baseDir, 'application/default/controllers', 'index.php'), $data);


$data = <<<eot
<?php

class Task_IndexController extends Tii_Application_Controller_Abstract
{
	public function indexAction()
	{}
}
eot;
file_put_contents(Tii_Filesystem::concat($baseDir, 'application/task/controllers', 'index.php'), $data);


print("output: " . $baseDir . "\n");