<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$config = require 'config.php';
$connection = new AMQPStreamConnection($config['rabbitmq']['host'], $config['rabbitmq']['port'], $config['rabbitmq']['username'], $config['rabbitmq']['password']);
$channel = $connection->channel();
$channel->queue_declare(
    'main_queue',
    false,
    true,
    false,
    false,
    false,
    [
        'x-dead-letter-exchange' => ['S', 'dlx_exchange'],
        'x-max-length' => ['I', 1000],
    ],
);

$callback = function (AMQPMessage $msg) use ($channel, $config) {
    $data = json_decode($msg->getBody(), true);
    $retries = $data['retries'] ?? 0;

    try {
        // Simulate processing
        echo " [x] Processing message: " . $data['message'] . "\n";
        if (rand(0, 1)) { // Simulate random failure
            throw new Exception('Simulated failure.');
        }
        // Acknowledge if processed successfully
        $channel->basic_ack($msg->getDeliveryTag());
    } catch (Exception $e) {
        echo " [x] Error: " . $e->getMessage() . "\n";
        $retries++;
        if ($retries < 5) {
            echo " [x] Retry attempt: $retries\n";
            // Requeue with updated retry count
            $data['retries'] = $retries;
            $msg = new AMQPMessage(json_encode($data), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
            $channel->basic_publish($msg, '', $config['rabbitmq']['main_queue']);
            $channel->basic_ack($msg->getDeliveryTag());
        } else {
            // Max retries reached, reject and move to DLQ
            echo " [x] Max retries reached. Moving to DLQ.\n";
            $channel->basic_nack($msg->getDeliveryTag(), false, false);
        }
    }
};

$channel->basic_consume($config['rabbitmq']['main_queue'], '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
