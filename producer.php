<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$config = require 'config.php';
$connection = new AMQPStreamConnection($config['rabbitmq']['host'], $config['rabbitmq']['port'], $config['rabbitmq']['username'], $config['rabbitmq']['password']);
$channel = $connection->channel();

// Declare main queue and bind to DLX
$channel->queue_declare(
    $config['rabbitmq']['main_queue'],
    false,
    true,
    false,
    false,
    false,
    [
        'x-dead-letter-exchange' => ['S', $config['rabbitmq']['dlx_exchange']],
        'x-max-length' => ['I', 1000],
    ]
);

// Publish a message
$data = json_encode(['message' => 'Hello RabbitMQ!', 'retries' => 0]);
$msg = new AMQPMessage($data, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
$channel->basic_publish($msg, '', $config['rabbitmq']['main_queue']);

echo " [x] Sent 'Hello RabbitMQ!'\n";

$channel->close();
$connection->close();
