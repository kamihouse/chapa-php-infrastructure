<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration\KafkaTopicConfiguration;
use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Connection\KafkaConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\EnqueueHeader;
use Ecotone\Enqueue\{EnqueueInboundChannelAdapterBuilder, InboundMessageConverter};
use Ecotone\Enqueue\HttpReconnectableConnectionFactory;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ramsey\Uuid\Uuid;

final class KafkaInboundChannelAdapterBuilder extends EnqueueInboundChannelAdapterBuilder
{
    public function __construct(string $messageChannelName, string $endpointId, ?string $requestChannelName, string $connectionReferenceName, private ?KafkaTopicConfiguration $topicConfig)
    {
        parent::__construct(
            $messageChannelName,
            $endpointId,
            $requestChannelName,
            $connectionReferenceName,
        );
    }

    public static function createWith(string $endpointId, string $topicName, ?string $requestChannelName, string $connectionReferenceName = KafkaConnectionFactory::class, ?KafkaTopicConfiguration $topicConfig = null): self
    {
        return new self($topicName, $endpointId, $requestChannelName, $connectionReferenceName, $topicConfig);
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        $connectionFactory = new Definition(CachedConnectionFactory::class, [
            new Definition(HttpReconnectableConnectionFactory::class, [
                new Reference($this->connectionReferenceName),
                Uuid::uuid4()->toString(),
            ]),
        ], 'createFor');

        $inboundMessageConverter = new Definition(InboundMessageConverter::class, [
            $this->endpointId,
            $this->acknowledgeMode,
            DefaultHeaderMapper::createWith($this->headerMapper, []),
            EnqueueHeader::HEADER_ACKNOWLEDGE,
        ]);

        return new Definition(KafkaInboundChannelAdapter::class, [
            $connectionFactory,
            $this->declareOnStartup,
            $this->messageChannelName,
            $this->receiveTimeoutInMilliseconds,
            $inboundMessageConverter,
            new Reference(ConversionService::REFERENCE_NAME)
        ]);
    }
}
