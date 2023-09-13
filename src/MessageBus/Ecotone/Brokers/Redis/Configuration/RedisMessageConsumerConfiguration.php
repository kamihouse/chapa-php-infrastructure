<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Redis\Configuration;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Redis\Connection\RedisConnectionFactory;
use Ecotone\Enqueue\EnqueueMessageConsumerConfiguration;
use Ecotone\Redis\RedisInboundChannelAdapterBuilder;

final class RedisMessageConsumerConfiguration extends EnqueueMessageConsumerConfiguration
{
    private bool $declareOnStartup = RedisInboundChannelAdapterBuilder::DECLARE_ON_STARTUP_DEFAULT;

    public static function create(string $endpointId, string $queueName, string $connectionReferenceName = RedisConnectionFactory::class): self
    {
        return new self(
            $endpointId,
            $queueName,
            $connectionReferenceName
        );
    }

    public function withDeclareOnStartup(bool $declareOnStartup): self
    {
        $this->declareOnStartup = $declareOnStartup;

        return $this;
    }

    public function isDeclaredOnStartup(): bool
    {
        return $this->declareOnStartup;
    }
}
