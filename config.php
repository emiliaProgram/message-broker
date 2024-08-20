<?php

return [
    'rabbitmq' => [
        'host' => 'localhost',
        'port' => 5672,
        'username' => 'guest',
        'password' => 'guest',
        'main_queue' => 'main_queue',
        'dlx_exchange' => 'dlx_exchange',
        'dlx_queue' => 'dead_letter_queue',
    ],
];
