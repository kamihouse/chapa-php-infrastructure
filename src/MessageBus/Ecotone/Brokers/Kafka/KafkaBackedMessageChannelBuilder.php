<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration\KafkaTopicConfiguration;
use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Connection\KafkaConnectionFactory;
use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders\DefaultMessageHeader;
use Ecotone\Enqueue\EnqueueMessageChannelBuilder;

final class KafkaBackedMessageChannelBuilder extends EnqueueMessageChannelBuilder
{
    private function __construct(string $topicName, string $connectionReferenceName, string $messageBrokerHeadersReferenceName, ?KafkaTopicConfiguration $topicConfig)
    {
        parent::__construct(
            KafkaInboundChannelAdapterBuilder::createWith(
                $topicName,
                $topicName,
                null,
                $connectionReferenceName,
                $topicConfig
            ),
            KafkaOutboundChannelAdapterBuilder::create(
                $topicName,
                $connectionReferenceName,
                $messageBrokerHeadersReferenceName,
                $topicConfig
            )
        );
    }

    public static function create(string $topicName, string $connectionReferenceName = KafkaConnectionFactory::class, string $messageBrokerHeadersReferenceName = DefaultMessageHeader::class, ?KafkaTopicConfiguration $topicConfig = null): self
    {
        return new self($topicName, $connectionReferenceName, $messageBrokerHeadersReferenceName, $topicConfig);
    }
}
