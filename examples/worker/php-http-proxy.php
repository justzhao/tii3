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
    $connection->send("message: $buffer\n");
    $connection->send("sequenceId: ".Tii_Math::getSequenceId()."\n");

   // sleep(rand(1, 2));
};

// Run.
Tii_Worker::run();