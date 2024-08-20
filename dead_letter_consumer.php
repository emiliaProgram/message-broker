<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$config = require 'config.php';
$connection = new AMQPStreamConnection($config['rabbitmq']['host'], $config['rabbitmq']['port'], $config['rabbitmq']['username'], $config['rabbitmq']['password']);
$channel = $connection->channel();

$channel->queue_declare($config['rabbitmq']['dlx_queue'], false, true, false, false);

$callback = function (AMQPMessage $msg) {
    echo " [x] Dead Letter Message: " . $msg->getBody() . "\n";
    // Acknowledge the dead letter message
    $msg->getChannel()->basic_ack($msg->getDeliveryTag());
};

$channel->basic_consume($config['rabbitmq']['dlx_queue'], '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
