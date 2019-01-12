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
 * @version $Id: packer.php 8923 2017-11-19 11:49:34Z alacner $
 */

require_once __DIR__ . '/../../Bootstrap.php';
Tii_Config::setDir(__DIR__.'/../');
$output = $argv[1] ?: sys_get_temp_dir();
$packer = new Tii_Packer(
	__DIR__.'/../../',
	Tii_Filesystem::concat(
		$output,
		'Tii-' . preg_replace('|^(\d+\.\d+)(.*)|i', '$1', Tii_Version::VERSION) .'.php'
	)
);
$packer->exclude('.configs/*');
$packer->setUglify("i");
$packer->excludeVar('$__file_', '$__viewer_', '$__return_', '$_viewer');//render under extract;
$packer->excludeVar('$print', '$print_backtrace_priority', '$priority', '$priorities', '$priorityNames');//Tii_Logger
$packer->excludeVar('$lang');//Tii_Config
$packer->excludeVar('$units');//Tii_Filesystem
$packer->excludeVar('$options', '$macPattern', '$ipPattern');//Tii_Application*
$packer->excludeVar('$nestedTransactionCount', '$preparedStatements', '$cachePreparedStatements');//Tii_Dao*
$packer->excludeVar(//Tii_Worker
    '$id', '$connections', '$host', '$dsn', '$protocol', '$pauseAccept', '$socket', '$socketName',
    '$context', '$name', '$runtime', '$init', '$globalStart', '$startFile', '$daemonize', '$events',
    '$onMasterReload', '$onMasterStop', '$stdoutFile', '$status', '$pid', '$pids', '$ids', '$workers', '$statistics',
    '$onWorkerStart', '$onWorkerReload', '$onWorkerStop', '$onConnect', '$onMessage', '$onClose', '$onBufferFull',
    '$onBufferDrain', '$onError'
);
//Tii_Worker*
$packer->excludeVar('$clients', '$id', '$socket', '$remote_socket', '$timeout', '$protocol', '$type', '$onConnect', '$onMessage', '$onClose', '$onError');//Tii_Worker_Client
$packer->excludeVar(//Tii_Worker_Connection*
    '$socket', '$remoteAddress', '$protocol', '$transport', '$onConnect', '$onMessage', '$onClose',
    '$onBufferFull', '$onBufferDrain', '$onError', '$statistics'
);
$packer->excludeVar(//Tii_Worker*
    '$worker', '$status', '$remoteHost', '$remoteURI', '$connectStartTime', '$runtime',
    '$pidsToRestart', '$pidFile', '$tempfile', '$delayKillingTime',
    '$process_data', '$command', '$quietMode', '$forkMode', '$startFiles',
    '$events', '$signals', '$timers', '$id',
    '$maxSendBufferSize', '$maxPackageSize', '$idRecorder', '$sendBuffer', '$recvBuffer', '$currentPackageLength',
    '$sslHandshakeCompleted', '$isPaused', '$bytesRead', '$bytesWritten'
);

$packer->priority(
	//Bootstrap
	'Bootstrap.php',
	'Version.php',
	//ConfigWrapper
	'ConfigWrapper.php',
	//Exception
	'Exception.php',
	'Application/Exception.php',
	'Application/IgnoreException.php',
	'Application/Controller/Exception.php',
	'Dao/Exception.php',
	//Abstract
	'Logger/Abstract.php',
	'Cache/Abstract.php',
	'Application/Abstract.php',
	'Application/Session/Abstract.php',
	'Application/Processor/Abstract.php',
	'Application/Helper/Pager/Abstract.php',
	'Application/Controller/Abstract.php',
	'Application/Controller/ErrorAbstract.php',
	//Event
	'Event.php',
	//Worker
	'Worker/Event.php',
	'Worker/Event/Abstract.php',
	'Worker/Callable.php',
	'Worker/Connection.php',
	'Worker/Connection/Abstract.php'
);
$packer->execute(true, NULL, [<<<eot
<?php
/**
 * !!! AUTOMATICALLY GENERATED FILE. DO NOT MODIFY !!!
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
 * @license http://www.tiiframework.com/license
 * @link http://www.tiiframework.com/
 * @link http://www.tiichina.com/
 */
?>
eot
]);