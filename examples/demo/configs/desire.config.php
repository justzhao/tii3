<?php
return array(
	'debug_mode' => true, //开启debug模式
    'logger' => array(
        'handler' => array('Desire_Logger_File'),
        'priority' => Desire_Logger_Constant::ALL, //日志的等级
    ),
	'temp_dir' => sys_get_temp_dir(), //临时文件路径 **
	'library' => array(
		'include' => array(
			realpath(CONFIGURATION_DIRECTORY . '/../../extras/classes'),
		),
		'Tattoo' => realpath(CONFIGURATION_DIRECTORY . '/../../extras/extends/Tattoo'),
		'*' => realpath(CONFIGURATION_DIRECTORY . '/../library'),
	),
	'logger' => array('Desire_Logger_File', CONFIGURATION_DIRECTORY . '/../log'),
	'application' => array(
		//'instance' => null, //instance
		'directory' => realpath(CONFIGURATION_DIRECTORY . '/../application'),//默认的应用目录
		//
		'module' => 'default',//default module name
		'controller' => 'index',//default controller name
		'action' => 'index',//default action name
		'filters' => array(
			'application.processor' => function($processor) {
					switch ($processor->getModuleName()) {
						case 'task':
							if ($processor->getProcessorName() !== 'cli') {
								die("only running with cli mode");
							}
							set_time_limit(0);
							ini_set('zend.enable_gc', true);
							ini_set('memory_limit', '1024M');
							break;
						default:
					}
					return $processor;
				},
			//'application.filter.before' => function($controllerInstance) {return $controllerInstance;},
			//'application.filter.after' => function($controllerInstance) {return $controllerInstance;},
		),
		//
		'cookie' => array(//cookie
			'path' => '/',
			'domain' => null,
			'secure' => false,
			'httponly' => false,
		),
	),
	//database
	'database' => array(
		'dsn' => array(
			'host' => 'localhost',
			'port' => 3306,
			'dbname' => 'dbname',
		),
		'username' => 'root',
		'passwd' => 'kernel',
	),
	//cache
	'cache' => array(
		'chain' => array('memcache', 'apc', 'file'),//可以用Desire_Cache->setChain()设置
		'memcache' => array(
			'server1' => array('localhost'),
		),
	),
);
