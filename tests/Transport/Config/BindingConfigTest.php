<?php

declare(strict_types=1);

namespace Jwage\PhpAmqpLibMessengerBundle\Tests\Transport\Config;

use InvalidArgumentException;
use Jwage\PhpAmqpLibMessengerBundle\Transport\Config\BindingConfig;
use PHPUnit\Framework\TestCase;

class BindingConfigTest extends TestCase
{
    public function testEmptyConstruct(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Binding routing key is required');

        new BindingConfig();
    }

    public function testDefaultConstruct(): void
    {
        self::assertDefaultBindingConfig(new BindingConfig(routingKey: 'routing_key'));
    }

    public function testFromArrayWithEmptyArray(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Binding routing key is required');

        BindingConfig::fromArray([]);
    }

    /** @psalm-suppress InvalidArgument */
    public function testFromArrayWithInvalidBindingConfig(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Invalid binding option(s) "invalid" passed to the AMQP Messenger transport.');

        BindingConfig::fromArray(['invalid' => true]);
    }

    public function testFromArray(): void
    {
        $bindingConfig = BindingConfig::fromArray([
            'routing_key' => 'routing_key',
            'arguments' => ['arg1' => 'value1', 'arg2' => 'value2'],
        ]);

        self::assertSame('routing_key', $bindingConfig->routingKey);
        self::assertSame(['arg1' => 'value1', 'arg2' => 'value2'], $bindingConfig->arguments);
    }

    private static function assertDefaultBindingConfig(BindingConfig $bindingConfig): void
    {
        self::assertSame('routing_key', $bindingConfig->routingKey);
        self::assertSame([], $bindingConfig->arguments);
    }
}
