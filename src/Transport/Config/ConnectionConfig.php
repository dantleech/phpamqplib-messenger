<?php

declare(strict_types=1);

namespace Jwage\PhpAmqpLibMessengerBundle\Transport\Config;

use InvalidArgumentException;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use SensitiveParameter;

use function array_diff;
use function array_keys;
use function count;
use function implode;
use function is_string;
use function sprintf;

readonly class ConnectionConfig
{
    public const int DEFAULT_PREFETCH_COUNT = 1;

    public const int DEFAULT_WAIT_TIMEOUT = 1;

    public const int DEFAULT_CONFIRM_TIMEOUT = 3;

    private const array AVAILABLE_OPTIONS = [
        'auto_setup',
        'host',
        'port',
        'user',
        'password',
        'vhost',
        'insist',
        'login_method',
        'locale',
        'connection_timeout',
        'read_timeout',
        'write_timeout',
        'channel_rpc_timeout',
        'heartbeat',
        'keepalive',
        'prefetch_count',
        'wait_timeout',
        'confirm_enabled',
        'confirm_timeout',
        'ssl',
        'exchange',
        'delay',
        'queues',
    ];

    public bool $autoSetup;

    public string $host;

    public int $port;

    public string $user;

    public string $password;

    public string $vhost;

    public bool $insist;

    public string $loginMethod;

    public string $locale;

    public float $connectionTimeout;

    public float $readTimeout;

    public float $writeTimeout;

    public float $channelRPCTimeout;

    public int $heartbeat;

    public bool $keepalive;

    public int $prefetchCount;

    public int|float|null $waitTimeout;

    public bool $confirmEnabled;

    public int|float $confirmTimeout;

    public ExchangeConfig $exchange;

    public DelayConfig $delay;

    /** @var array<string, QueueConfig> */
    public array $queues;

    /**
     * @param array<int|string, QueueConfig> $queues
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        bool|null $autoSetup = null,
        string|null $host = null,
        int|null $port = null,
        string|null $user = null,
        #[SensitiveParameter]
        string|null $password = null,
        string|null $vhost = null,
        bool|null $insist = null,
        string|null $loginMethod = null,
        string|null $locale = null,
        float|null $connectionTimeout = null,
        float|null $readTimeout = null,
        float|null $writeTimeout = null,
        float|null $channelRPCTimeout = null,
        int|null $heartbeat = null,
        bool|null $keepalive = null,
        int|null $prefetchCount = null,
        int|float|null $waitTimeout = null,
        bool|null $confirmEnabled = null,
        int|float|null $confirmTimeout = null,
        public SslConfig|null $ssl = null,
        ExchangeConfig|null $exchange = null,
        DelayConfig|null $delay = null,
        array|null $queues = null,
    ) {
        if ($waitTimeout === 0 || $waitTimeout === 0.0) {
            throw new InvalidArgumentException('Connection wait timeout cannot be zero. This will cause the consumer to wait forever and block the messenger worker loop.');
        }

        $this->autoSetup         = $autoSetup ?? true;
        $this->host              = $host ?? 'localhost';
        $this->port              = $port ?? 5672;
        $this->user              = $user ?? 'guest';
        $this->password          = $password ?? 'guest';
        $this->vhost             = $vhost ?? '/';
        $this->insist            = $insist ?? false;
        $this->loginMethod       = $loginMethod ?? AMQPConnectionConfig::AUTH_AMQPPLAIN;
        $this->locale            = $locale ?? 'en_US';
        $this->connectionTimeout = $connectionTimeout ?? 3.0;
        $this->readTimeout       = $readTimeout ?? 3.0;
        $this->writeTimeout      = $writeTimeout ?? 3.0;
        $this->channelRPCTimeout = $channelRPCTimeout ?? 3.0;
        $this->heartbeat         = $heartbeat ?? 0;
        $this->keepalive         = $keepalive ?? true;
        $this->prefetchCount     = $prefetchCount ?? self::DEFAULT_PREFETCH_COUNT;
        $this->waitTimeout       = $waitTimeout ?? self::DEFAULT_WAIT_TIMEOUT;
        $this->confirmEnabled    = $confirmEnabled ?? true;
        $this->confirmTimeout    = $confirmTimeout ?? self::DEFAULT_CONFIRM_TIMEOUT;
        $this->exchange          = $exchange ?? new ExchangeConfig();
        $this->delay             = $delay ?? new DelayConfig();
        $this->queues            = self::indexByQueueName($queues ?? []);
    }

    /**
     * @param array{
     *     auto_setup?: bool,
     *     host?: string,
     *     port?: int|mixed,
     *     user?: string,
     *     password?: string,
     *     vhost?: string,
     *     insist?: bool|mixed,
     *     login_method?: string,
     *     locale?: string,
     *     connection_timeout?: float|mixed,
     *     read_timeout?: float|mixed,
     *     write_timeout?: float|mixed,
     *     channel_rpc_timeout?: float|mixed,
     *     heartbeat?: int|mixed,
     *     keepalive?: bool|mixed,
     *     prefetch_count?: int|mixed,
     *     wait_timeout?: int|float|mixed,
     *     confirm_enabled?: bool|mixed,
     *     confirm_timeout?: int|float|mixed,
     *     ssl?: array{
     *         cafile?: string|null,
     *         capath?: string|null,
     *         local_cert?: string|null,
     *         local_pk?: string|null,
     *         verify_peer?: bool|null,
     *         verify_peer_name?: bool|null,
     *         passphrase?: string|null,
     *         ciphers?: string|null,
     *         security_level?: int|null,
     *         crypto_method?: int|null,
     *     },
     *     exchange?: array{
     *         name?: string,
     *         default_publish_routing_key?: string,
     *         type?: string,
     *         passive?: bool|mixed,
     *         durable?: bool|mixed,
     *         auto_delete?: bool|mixed,
     *         arguments?: array<string, mixed>,
     *     },
     *     delay?: array{
     *         exchange?: array{
     *             name?: string,
     *             default_publish_routing_key?: string,
     *             type?: string,
     *             passive?: bool|mixed,
     *             durable?: bool|mixed,
     *             auto_delete?: bool|mixed,
     *             arguments?: array<string, mixed>,
     *         },
     *         queue_name_pattern?: string,
     *     },
     *     queues?: array<int|string, array{
     *         name?: string,
     *         prefetch_count?: int|mixed,
     *         wait_timeout?: int|float|mixed,
     *         passive?: bool|mixed,
     *         durable?: bool|mixed,
     *         exclusive?: bool|mixed,
     *         auto_delete?: bool|mixed,
     *         bindings?: array<int|string, array{
     *             routing_key?: string,
     *             arguments?: array<string, mixed>,
     *         }|null>,
     *         arguments?: array<string, mixed>,
     *     }|null>,
     * } $connectionConfig
     *
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $connectionConfig): self
    {
        self::validate($connectionConfig);

        $prefetchCount = isset($connectionConfig['prefetch_count'])
            ? (int) $connectionConfig['prefetch_count'] : null;

        $waitTimeout = isset($connectionConfig['wait_timeout'])
            ? (float) $connectionConfig['wait_timeout'] : null;

        $confirmEnabled = isset($connectionConfig['confirm_enabled'])
            ? (bool) $connectionConfig['confirm_enabled'] : null;

        $confirmTimeout = isset($connectionConfig['confirm_timeout'])
            ? (float) $connectionConfig['confirm_timeout'] : null;

        $queues = $connectionConfig['queues'] ?? [];

        $queueConfigs = [];

        foreach ($queues as $queueName => $queue) {
            $queue ??= [];

            if (! isset($queue['name']) && is_string($queueName)) {
                $queue['name'] = $queueName;
            }

            if ($prefetchCount !== null && ! isset($queue['prefetch_count'])) {
                $queue['prefetch_count'] = $prefetchCount;
            }

            if ($waitTimeout !== null && ! isset($queue['wait_timeout'])) {
                $queue['wait_timeout'] = $waitTimeout;
            }

            $queueName = $queue['name'] ?? '';

            $queueConfigs[$queueName] = QueueConfig::fromArray($queue);
        }

        return new self(
            autoSetup: $connectionConfig['auto_setup'] ?? null,
            host: $connectionConfig['host'] ?? null,
            port: isset($connectionConfig['port']) ? (int) $connectionConfig['port'] : null,
            user: $connectionConfig['user'] ?? null,
            password: $connectionConfig['password'] ?? null,
            vhost: $connectionConfig['vhost'] ?? null,
            insist: isset($connectionConfig['insist']) ? (bool) $connectionConfig['insist'] : null,
            loginMethod: $connectionConfig['login_method'] ?? null,
            locale: $connectionConfig['locale'] ?? null,
            connectionTimeout: isset($connectionConfig['connection_timeout']) ? (float) $connectionConfig['connection_timeout'] : null,
            readTimeout: isset($connectionConfig['read_timeout']) ? (float) $connectionConfig['read_timeout'] : null,
            writeTimeout: isset($connectionConfig['write_timeout']) ? (float) $connectionConfig['write_timeout'] : null,
            channelRPCTimeout: isset($connectionConfig['channel_rpc_timeout']) ? (float) $connectionConfig['channel_rpc_timeout'] : null,
            heartbeat: isset($connectionConfig['heartbeat']) ? (int) $connectionConfig['heartbeat'] : null,
            keepalive: isset($connectionConfig['keepalive']) ? (bool) $connectionConfig['keepalive'] : null,
            prefetchCount: $prefetchCount,
            waitTimeout: $waitTimeout,
            confirmEnabled: $confirmEnabled,
            confirmTimeout: $confirmTimeout,
            ssl: isset($connectionConfig['ssl']) ? SslConfig::fromArray($connectionConfig['ssl']) : null,
            exchange: isset($connectionConfig['exchange']) ? ExchangeConfig::fromArray($connectionConfig['exchange']) : null,
            delay: isset($connectionConfig['delay']) ? DelayConfig::fromArray($connectionConfig['delay']) : null,
            queues: $queueConfigs,
        );
    }

    /** @return array<string> */
    public function getQueueNames(): array
    {
        return array_keys($this->queues);
    }

    /** @throws InvalidArgumentException */
    public function getQueueConfig(string $queueName): QueueConfig
    {
        if (! isset($this->queues[$queueName])) {
            throw new InvalidArgumentException(sprintf('Queue "%s" not found', $queueName));
        }

        return $this->queues[$queueName];
    }

    /**
     * @param array<string, mixed> $connectionConfig
     *
     * @throws InvalidArgumentException
     */
    private static function validate(array $connectionConfig): void
    {
        if (0 < count($invalidOptions = array_diff(array_keys($connectionConfig), self::AVAILABLE_OPTIONS))) {
            throw new InvalidArgumentException(sprintf('Invalid option(s) "%s" passed to the AMQP Messenger transport.', implode('", "', $invalidOptions)));
        }
    }

    /**
     * @param array<int|string, QueueConfig> $queues
     *
     * @return array<string, QueueConfig>
     */
    private static function indexByQueueName(array $queues): array
    {
        $indexedQueues = [];

        foreach ($queues as $key => $queue) {
            if (is_string($key) && $queue->name !== $key) {
                throw new InvalidArgumentException(sprintf(
                    'Queue name "%s" does not match array key "%s"',
                    $queue->name,
                    $key,
                ));
            }

            $indexedQueues[$queue->name] = $queue;
        }

        return $indexedQueues;
    }
}
