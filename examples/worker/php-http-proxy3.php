<?php
require_once __DIR__ . '/../../library/Bootstrap.php';
Tii_Config::setDir(__DIR__.'/../');

// Create a TCP worker.
$worker = new Tii_Worker('tcp://0.0.0.0:8088');
// 6 processes
$worker->count = 6;
// Worker name.
$worker->name = 'php-http-proxy';

// Emitted when data received from client.
$worker->onMessage = function($connection, $buffer)
{
	// Parse http header.
	list($method, $addr, $http_version) = explode(' ', $buffer);
	$url_data = parse_url($addr);
	$addr = !isset($url_data['port']) ? "{$url_data['host']}:80" : "{$url_data['host']}:{$url_data['port']}";
	// Async TCP connection.
	$remote_connection = new Tii_Worker_Connection_Async("tcp://$addr");
	// CONNECT.
	if ($method !== 'CONNECT') {
		$remote_connection->send($buffer);
		// POST GET PUT DELETE etc.
	} else {
		$connection->send("HTTP/1.1 200 Connection Established\r\n\r\n");
	}
	// Pipe.
	$remote_connection ->pipe($connection);
	$connection->pipe($remote_connection);
	$remote_connection->connect();
};

// Run.
Tii_Worker::run();