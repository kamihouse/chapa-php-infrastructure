<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Redis\Connection;

use Enqueue\Redis\Redis;
use Enqueue\Redis\RedisContext;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class RedisConnectionFactory implements ConnectionFactory
{
    private $config;

    /**
     * @var Redis
     */
    private $redis;

    public function __construct(array|Redis $config)
    {
        if ($config instanceof Redis) {
            $this->redis = $config;
            $this->config = $this->defaultConfig();

            return;
        }

        $this->config = array_replace($this->defaultConfig(), $config);
    }

    public function createContext(): Context
    {
        if ($this->config['lazy']) {
            return new RedisContext(function () {
                return $this->createRedis();
            }, (int) $this->config['redelivery_delay']);
        }

        return new RedisContext($this->createRedis(), (int) $this->config['redelivery_delay']);
    }

    private function createRedis(): Redis
    {
        if (!is_array($this->config) || empty($this->config)) {
            throw new \Exception('array connection for custom redis connection is invalid');
        }

        if (!$this->redis instanceof Redis) {
            $this->redis = new PRedis($this->config);
            $this->redis->connect();
        }

        return $this->redis;
    }

    private function defaultConfig(): array
    {
        return [
            'scheme' => 'redis',
            'scheme_extensions' => [],
            'host' => '127.0.0.1',
            'port' => 6379,
            'path' => null,
            'database' => null,
            'username' => null,
            'password' => null,
            'async' => false,
            'persistent' => false,
            'lazy' => true,
            'timeout' => 5.0,
            'read_write_timeout' => null,
            'predis_options' => null,
            'ssl' => null,
            'redelivery_delay' => 300,
        ];
    }
}
