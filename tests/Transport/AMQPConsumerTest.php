<?php

declare(strict_types=1);

namespace Jwage\PhpAmqpLibMessengerBundle\Tests\Transport;

use Closure;
use Jwage\PhpAmqpLibMessengerBundle\Tests\TestCase;
use Jwage\PhpAmqpLibMessengerBundle\Transport\AMQPConsumer;
use Jwage\PhpAmqpLibMessengerBundle\Transport\Config\ConnectionConfig;
use Jwage\PhpAmqpLibMessengerBundle\Transport\Config\QueueConfig;
use Jwage\PhpAmqpLibMessengerBundle\Transport\Connection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;

class AMQPConsumerTest extends TestCase
{
    /** @var MockObject&Connection */
    private Connection $connection;

    /** @var MockObject&ConnectionConfig */
    private ConnectionConfig $connectionConfig;

    private QueueConfig $queueConfig;

    private AMQPConsumer $consumer;

    public function testConsume(): void
    {
        $channel = $this->createMock(AMQPChannel::class);

        $this->connection->expects(self::any())
            ->method('channel')
            ->willReturn($channel);

        $this->connection->expects(self::any())
            ->method('getQueueNames')
            ->willReturn(['test_queue']);

        $channel->expects(self::once())
            ->method('basic_qos')
            ->with(
                prefetch_size: 0,
                prefetch_count: 5,
                a_global: false,
            );

        $channel->expects(self::once())
            ->method('basic_consume')
            ->with(
                queue: 'test_queue',
                consumer_tag: '',
                no_local: false,
                no_ack: false,
                exclusive: false,
                nowait: false,
                callback: self::isInstanceOf(Closure::class),
            );

        $channel->expects(self::exactly(2))
            ->method('wait')
            ->with(
                allowed_methods: null,
                non_blocking: true,
            );

        $amqpEnvelope = $this->consumer->get('test_queue');

        self::assertNull($amqpEnvelope);

        $message = $this->createMock(AMQPMessage::class);

        $this->consumer->callback($message);

        $amqpEnvelope = $this->consumer->get('test_queue');

        self::assertNotNull($amqpEnvelope);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);

        $this->connectionConfig = $this->createMock(ConnectionConfig::class);

        $this->queueConfig = new QueueConfig();

        $this->connectionConfig->expects(self::any())
            ->method('getQueueConfig')
            ->willReturn($this->queueConfig);

        $this->consumer = new AMQPConsumer($this->connection, $this->connectionConfig);
    }
}
