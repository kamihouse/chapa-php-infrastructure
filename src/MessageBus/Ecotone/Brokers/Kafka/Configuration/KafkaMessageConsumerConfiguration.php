<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Connection\KafkaConnectionFactory;
use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\KafkaInboundChannelAdapterBuilder;
use Ecotone\Enqueue\EnqueueMessageConsumerConfiguration;

final class KafkaMessageConsumerConfiguration extends EnqueueMessageConsumerConfiguration
{
    private bool $declareOnStartup = KafkaInboundChannelAdapterBuilder::DECLARE_ON_STARTUP_DEFAULT;

    public function __construct(string $endpointId, string $queueName, string $kafkaConnectionReferenceName = KafkaConnectionFactory::class, private ?KafkaTopicConfiguration $topicConfig = null)
    {
        parent::__construct($endpointId, $queueName, $kafkaConnectionReferenceName);
    }

    public static function create(string $endpointId, string $queueName, string $kafkaConnectionReferenceName = KafkaConnectionFactory::class, ?KafkaTopicConfiguration $topicConfig = null): self
    {
        return new self($endpointId, $queueName, $kafkaConnectionReferenceName, $topicConfig);
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

    public function getKafkaTopicConfiguration(): ?KafkaTopicConfiguration
    {
        return $this->topicConfig;
    }
}
