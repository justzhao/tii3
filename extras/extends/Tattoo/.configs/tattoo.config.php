<?php

return array(
	'application' => array(
		'controller' => array(
			'signature' => array(
				'expired' => 300,
			),
		)
	),

	'dzi' => array(
		'client' => array(
			'gateway' => 'http://api.foodabc.com/',
			'appkey' => '123',
			'secret_key' => '123456',
		)
	),

	'beanstalk' => array(
		'default' => array(
			'host' => 'localhost',
			'port' => 11300,
			'timeout' => 1,
		),
	),

	'solr' => array(
		'gateway' => 'http://127.0.0.1:8983/',
	),

	'nsq' => array(
		'tcp-address' => ['127.0.0.1:4160'],
		'http-address' => ['127.0.0.1:4161'],
		'timeout' => array(
			'connection' => 3,
			'read_write' => 3,
			'read_wait' => 15,
		),
	),
	//dubbo
	'dubbo' => array(
		'registry' => array(
			'address' => 'zookeeper://127.0.0.1:2181?backup=127.0.0.1:2181',
		),
	),
	//ozt
	'ozt' => array(
		'client' => array(
			'appkey' => 'appkey',
			'secretKey' => 'secretKey',
			'gatewayUrl' => "http://api.gw.oztcdn.com",
			'headers' => array(),
		)
	)
);
