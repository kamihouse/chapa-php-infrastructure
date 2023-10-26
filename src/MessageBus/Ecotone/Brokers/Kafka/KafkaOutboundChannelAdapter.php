<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka;

use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\EnqueueOutboundChannelAdapter;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessageConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Enqueue\RdKafka\RdKafkaTopic;
use Enqueue\RdKafka\Serializer;

final class KafkaOutboundChannelAdapter extends EnqueueOutboundChannelAdapter
{
    public function __construct(
        protected CachedConnectionFactory $connectionFactory,
        protected RdKafkaTopic $topic,
        protected bool $autoDeclare,
        protected OutboundMessageConverter $outboundMessageConverter,
        private ConversionService $conversionService,
        protected ?Serializer $customSerializer = null
    ) {
        parent::__construct(
            $connectionFactory,
            $topic,
            $autoDeclare,
            $outboundMessageConverter,
            $conversionService
        );
    }

    public function initialize(): void
    {
        $context = $this->connectionFactory->createContext();
        $context->createQueue($this->topic->getTopicName());
        if ($this->customSerializer != null) {
            $context->setSerializer($this->customSerializer);
        }
    }
}
