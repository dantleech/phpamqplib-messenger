<?php

declare(strict_types=1);

namespace Jwage\PhpAmqpLibMessengerBundle\Transport\Config;

use InvalidArgumentException;
use PhpAmqpLib\Exchange\AMQPExchangeType;

use function array_diff;
use function array_keys;
use function count;
use function implode;
use function sprintf;
use function str_replace;

final readonly class DelayConfig
{
    private const array AVAILABLE_OPTIONS = [
        'exchange',
        'queue_name_pattern',
        'arguments',
    ];

    public ExchangeConfig $exchange;

    public string $queueNamePattern;

    /** @param array<string, mixed> $arguments */
    public function __construct(
        ExchangeConfig|null $exchange = null,
        string|null $queueNamePattern = null,
        public array $arguments = [],
    ) {
        $this->exchange = $exchange ?? new ExchangeConfig(
            name: 'delays',
            type: AMQPExchangeType::DIRECT,
        );

        $this->queueNamePattern = $queueNamePattern ?? 'delay_%exchange_name%_%routing_key%_%delay%';
    }

    /**
     * @param array{
     *     exchange?: array{
     *         name?: string,
     *         default_publish_routing_key?: string,
     *         type?: string,
     *         passive?: bool|mixed,
     *         durable?: bool|mixed,
     *         auto_delete?: bool|mixed,
     *         arguments?: array<string, mixed>,
     *     },
     *     queue_name_pattern?: string,
     *     arguments?: array<string, mixed>,
     * } $delayConfig
     *
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $delayConfig): self
    {
        self::validate($delayConfig);

        return new self(
            exchange: isset($delayConfig['exchange']) ? ExchangeConfig::fromArray($delayConfig['exchange']) : null,
            queueNamePattern: $delayConfig['queue_name_pattern'] ?? null,
            arguments: $delayConfig['arguments'] ?? [],
        );
    }

    public function getQueueName(int $delay, string|null $routingKey, bool $isRetryAttempt): string
    {
        $action = $isRetryAttempt ? '_retry' : '_delay';

        return str_replace(
            ['%delay%', '%exchange_name%', '%routing_key%'],
            [
                $delay,
                $this->exchange->name,
                $routingKey ?? '',
            ],
            $this->queueNamePattern,
        ) . $action;
    }

    /**
     * @param array<string, mixed> $delayConfig
     *
     * @throws InvalidArgumentException
     */
    public static function validate(array $delayConfig): void
    {
        if (0 < count($invalidDelayOptions = array_diff(array_keys($delayConfig), self::AVAILABLE_OPTIONS))) {
            throw new InvalidArgumentException(sprintf('Invalid delay option(s) "%s" passed to the AMQP Messenger transport.', implode('", "', $invalidDelayOptions)));
        }
    }
}
