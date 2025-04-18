# PhpAmqpLibMessengerBundle Documentations

This bundle adds support for `php-amqplib/php-amqplib` to Symfony Messenger, providing an alternative way to connect to RabbitMQ using a pure PHP library instead of the [php-amqp](https://github.com/php-amqp/php-amqp) C extension.

## Installation

```bash
composer require jwage/phpamqplib-messenger
```

Make sure the bundled is enabled in `config/bundles.php`:

```php
return [
    // ...
    Jwage\PhpAmqpLibMessengerBundle\PhpAmqpLibMessengerBundle::class => ['all' => true],
];
```

## DSN Format

It is easy to configure the bundle using a DSN and the `config/packages/messenger.yaml` file. The DSN format for the transport is:

```
phpamqplib://username:password@localhost[:post]/vhost[/exchange]
```

For SSL/TLS connections, use:

```
phpamqplibs://username:password@localhost[:port]/vhost[/exchange]
```

## Minimum Configuration

The minimum configuration required is the transports name and the DSN.

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            orders:
                dsn: 'phpamqplib://guest:guest@localhost:5672/myvhost/orders'
```

The configuration above will create an exchange named `orders` and bind a queue named `orders` to it within the vhost `myvhost`.

## Advanced Configuration

The bundle supports all advanced RabbitMQ configuration options in your `config/packages/messenger.yaml` file. Here is an example configuration with all the default values you can customize:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            orders:
                dsn: 'phpamqplib://localhost:5672/%2f'
                options:
                    # Connection options
                    host: 'localhost'
                    port: 5672
                    user: 'guest'
                    password: 'guest'
                    vhost: '/'

                    # Timeout settings
                    connection_timeout: 3.0
                    read_timeout: 3.0
                    write_timeout: 3.0
                    channel_rpc_timeout: 3.0

                    # Heartbeat settings
                    heartbeat: 60
                    keepalive: true

                    # Prefetch settings
                    prefetch_count: 1

                    # Confirm settings
                    confirm_enabled: true
                    confirm_timeout: 3.0

                    # Consume wait settings
                    wait_timeout: 1

                    # SSL/TLS configuration
                    ssl:
                        cafile: '/path/to/ca_certificate.pem'
                        capath: '/path/to/ca_certificate_path'
                        local_cert: '/path/to/local_certificate.pem'
                        local_pk: '/path/to/local_private_key.pem'
                        verify_peer: true
                        verify_peer_name: true
                        passphrase: 'passphrase'
                        ciphers: 'TLS_AES_256_GCM_SHA384'
                        security_level: 2
                        crypto_method: !php/const:STREAM_CRYPTO_METHOD_ANY_CLIENT

                    # Exchange configuration
                    exchange:
                        name: 'orders_exchange'
                        type: 'fanout'
                        default_publish_routing_key: ''
                        passive: false
                        durable: true
                        auto_delete: false
                        arguments: []

                    # Queue configuration
                    queues:
                        orders_messages:
                            prefetch_count: 5 # overrides the connection prefetch_count: 1
                            wait_timeout: 2.0 # overrides the connection wait_timeout: 1.0
                            passive: false
                            durable: true
                            exclusive: false
                            auto_delete: false
                            bindings:
                                routing_key1:
                                    arguments: []
                                routing_key2:
                                    arguments: []
                            arguments: []

                    # Delay configuration
                    delay:
                        exchange:
                            name: 'delays'
                            type: 'direct'
                            default_publish_routing_key: ''
                            passive: false
                            durable: true
                            auto_delete: false
                            arguments: []
                        queue_name_pattern: 'delay_%exchange_name%_%routing_key%_%delay%'
```

Any option can be specified in the DSN as an alternative to defining it in the `messenger.yaml` file:

```
phpamqplib://guest:guest@localhost?heartbeat=60&read_timeout=5.0
```

## Batch Dispatching

The bundle supports batch dispatching of messages. You can inject the `Jwage\PhpAmqpLibMessengerBundle\BatchMessageInterface` service, which wraps the default message bus in your application and provides new methods for dispatching messages in batches:

- `dispatchBatches(iterable $messages, int $batchSize = 100): void`
- `dispatchInBatch(object $message, int $batchSize): void`
- `flush(): void`

If you have an iterable list of messages, you can use the `dispatchBatches` method to dispatch the messages in batches:

```php
$batchSize = 2;

// can be an array or any iterable
$messages = [...];

$batchMessageBus->dispatchBatches($messages, $batchSize);
```

You can also use the `dispatchInBatch` method if you want more low level control over the batching process. Be sure to call the `flush()` method afterwards to handle when you have an uneven number of messages in the last batch:

```php
$batchSize = 10;

foreach ($messages as $message) {
    $batchMessageBus->dispatchInBatch($message, $batchSize);
}

$batchMessageBus->flush();
```

The batch dispatching is controlled with the `AMQPBatchStamp` stamp. The above methods are shortcuts for the following:

```php
$batchSize = 10;

foreach ($messages as $message) {
    $envelope = Envelope::wrap($message, [new AMQPBatchStamp($batchSize)]);

    $batchMessageBus->dispatch($envelope);
}

$batchMessageBus->flush();
```
