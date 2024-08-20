
# PHP RabbitMQ Messaging Application with Retry and Dead Letter Queue

This is a simple PHP application demonstrating how to use RabbitMQ for message queuing with retry logic and a Dead Letter Queue (DLQ). The application consists of a producer that sends messages, a consumer that processes messages with retry capabilities, and a DLQ consumer that handles messages that have failed processing after maximum retries.

## Table of Contents

- [Features](#features)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Application Structure](#application-structure)
- [Usage](#usage)
  - [Starting RabbitMQ Server](#starting-rabbitmq-server)
  - [Running the Producer](#running-the-producer)
  - [Running the Consumer](#running-the-consumer)
  - [Running the Dead Letter Queue Consumer](#running-the-dead-letter-queue-consumer)
- [How It Works](#how-it-works)
  - [Producer](#producer)
  - [Consumer with Retry Logic](#consumer-with-retry-logic)
  - [Dead Letter Queue (DLQ) Consumer](#dead-letter-queue-dlq-consumer)
- [Error and Exception Handling](#error-and-exception-handling)
- [References](#references)

## Features

- **Message Queuing**: Implements basic message queuing using RabbitMQ.
- **Retry Logic**: Automatically retries message processing up to 5 times upon failure.
- **Dead Letter Queue**: Unprocessable messages after maximum retries are routed to a Dead Letter Queue for further inspection or handling.
- **Error Handling**: Robust error and exception handling mechanisms to manage processing failures.
- **Simple Interface**: Easy-to-understand and modify codebase suitable for learning and extension.

## Prerequisites

Before setting up and running the application, ensure you have the following installed on your system:

- **PHP (Version 7.4 or higher)**
- **Composer**: PHP dependency manager.
- **RabbitMQ Server**: Message broker for queuing.
- **Git**: Version control system (optional, for cloning the repository).

## Installation

Follow these steps to set up the application on your local machine.

### 1. Clone the Repository

```bash
git clone https://github.com/emiliaProgram/message-broker.git
cd message-broker
```

### 2. Install PHP Dependencies

Use Composer to install the required PHP libraries.

```bash
composer install
```

This will install the following dependencies:

- [`php-amqplib/php-amqplib`](https://github.com/php-amqplib/php-amqplib): A pure PHP AMQP library.

### 3. Install and Start RabbitMQ Server

#### Install RabbitMQ

**On Ubuntu/Debian:**

```bash
sudo apt-get update
sudo apt-get install rabbitmq-server -y
```

**On macOS using Homebrew:**

```bash
brew update
brew install rabbitmq
```

**On Windows:**

Download and install RabbitMQ from the [official website](https://www.rabbitmq.com/download.html).

#### Start RabbitMQ Server

```bash
sudo service rabbitmq-server start
```

Check the status:

```bash
sudo service rabbitmq-server status
```

You should see a message indicating that RabbitMQ is running.

### 4. Configure RabbitMQ Management Plugin (Optional)

Enable the management plugin to monitor RabbitMQ through a web interface.

```bash
sudo rabbitmq-plugins enable rabbitmq_management
```

Access the management console at [http://localhost:15672](http://localhost:15672) with default credentials (`guest` / `guest`).

## Configuration

All configuration settings are located in the `config.php` file at the root of the project.

```php
<?php

return [
    'rabbitmq' => [
        'host' => 'localhost',
        'port' => 5672,
        'username' => 'guest',
        'password' => 'guest',
        'main_queue' => 'main_queue',
        'retry_exchange' => 'retry_exchange',
        'retry_queue' => 'retry_queue',
        'dead_letter_exchange' => 'dead_letter_exchange',
        'dead_letter_queue' => 'dead_letter_queue',
        'retry_delay' => 5000, // Delay in milliseconds between retries
        'max_retries' => 5, // Maximum number of retry attempts
    ],
];
```

**Configuration Parameters:**

- **`host`**: RabbitMQ server host.
- **`port`**: RabbitMQ server port.
- **`username` & `password`**: Credentials for connecting to RabbitMQ.
- **`main_queue`**: Primary queue for incoming messages.
- **`retry_exchange` & `retry_queue`**: Exchange and queue for handling message retries.
- **`dead_letter_exchange` & `dead_letter_queue`**: Exchange and queue for dead-lettered messages.
- **`retry_delay`**: Delay between retries in milliseconds.
- **`max_retries`**: Maximum number of retry attempts before moving the message to DLQ.

**Note:** Adjust these settings according to your environment and requirements.

## Application Structure

```plaintext
php-rabbitmq-app/
├── config.php
├── producer.php
├── consumer.php
├── dead_letter_consumer.php
```

- **`config.php`**: Configuration file containing all necessary settings.
- **`producer.php`**: Script to publish messages to the main queue.
- **`consumer.php`**: Script to consume and process messages with retry logic.
- **`dead_letter_consumer.php`**: Script to consume and handle messages from the Dead Letter Queue.

## Usage

Follow these steps to run the application components.

### Starting RabbitMQ Server

Ensure that the RabbitMQ server is running before starting the application components.

```bash
sudo service rabbitmq-server start
```

### Running the Producer

The producer sends messages to the main queue.

```bash
php producer.php
```

**Sample Output:**

```
[x] Sent 'Hello RabbitMQ!'
```

You can modify `producer.php` to send different messages as needed.

### Running the Consumer

The consumer processes messages from the main queue with retry logic.

```bash
php consumer.php
```

**Sample Output:**

```
[x] Received message: Hello RabbitMQ!
[x] Processing message...
[x] Error processing message: Simulated processing error.
[x] Retry attempt: 1
```

The consumer will attempt to process the message up to 5 times before sending it to the Dead Letter Queue upon persistent failure.

### Running the Dead Letter Queue Consumer

The DLQ consumer processes messages from the Dead Letter Queue for further inspection or handling.

```bash
php dead_letter_consumer.php
```

**Sample Output:**

```
[x] Received dead letter message: Hello RabbitMQ!
[x] Handling dead letter message...
```

## How It Works

### Producer

- **Connects** to RabbitMQ using provided configurations.
- **Declares** the necessary exchanges and queues:
  - **Main Queue**: Where messages are initially sent.
  - **Retry and Dead Letter Exchanges/Queues**: For handling retries and dead letters.
- **Publishes** messages to the main queue.

### Consumer with Retry Logic

- **Connects** to RabbitMQ and **consumes** messages from the main queue.
- **Processes** each message and **catches exceptions** during processing.
- **Implements retry logic**:
  - If processing fails, increments `retry_count` and publishes the message to the **retry queue** with a delay.
  - If `retry_count` exceeds `max_retries`, the message is routed to the **Dead Letter Queue**.
  
**Retry Mechanism:**

- Uses a **delayed queue** for retries by setting a TTL (Time-To-Live) on messages.
- After TTL expires, messages are routed back to the main queue for reprocessing.

### Dead Letter Queue (DLQ) Consumer

- **Connects** to RabbitMQ and **consumes** messages from the dead letter queue.
- **Processes** or **logs** dead-lettered messages for analysis or manual intervention.

## Error and Exception Handling

The application includes comprehensive error and exception handling to ensure robustness.

- **Try-Catch Blocks**: Wraps message processing logic to catch and handle exceptions.
- **Logging Errors**: Outputs error messages to the console; can be extended to log to files or monitoring systems.
- **Controlled Retries**: Ensures that messages are retried a limited number of times to prevent infinite loops.
- **Dead Lettering**: Unrecoverable messages are moved to DLQ for further inspection.


## References

- [RabbitMQ Official Website](https://www.rabbitmq.com/)
- [php-amqplib GitHub Repository](https://github.com/php-amqplib/php-amqplib)
- [RabbitMQ Tutorials](https://www.rabbitmq.com/getstarted.html)
- [Composer Dependency Manager](https://getcomposer.org/)
- [PHP Official Documentation](https://www.php.net/docs.php)

