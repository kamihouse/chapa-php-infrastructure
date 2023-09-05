<?php

declare(strict_types=1);

namespace FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers\Kafka;

use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessageConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Message;
use Enqueue\RdKafka\RdKafkaTopic;
use FretePago\Core\Domain\Event;
use FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers\CustomEnqueueOutboundChannelAdapter;
use FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders\IHeaderMessage;
use Interop\Queue\Message as buildMessageReturn;

final class KafkaOutboundChannelAdapter extends CustomEnqueueOutboundChannelAdapter
{
    public function __construct(
        CachedConnectionFactory $connectionFactory,
        private RdKafkaTopic $topic,
        bool $autoDeclare,
        OutboundMessageConverter $outboundMessageConverter,
        ConversionService $conversionService,
        IHeaderMessage $messageBrokerHeaders
    ) {
        parent::__construct(
            $connectionFactory,
            $topic,
            $autoDeclare,
            $outboundMessageConverter,
            $conversionService,
            $messageBrokerHeaders
        );
    }

    public function initialize(): void
    {
        $context = $this->connectionFactory->createContext();
        $context->createQueue($this->topic->getTopicName());
    }

    public function buildMessage(Message $message): buildMessageReturn
    {
        /** @var \Enqueue\RdKafka\RdKafkaMessage */
        $kafkaMessage = parent::buildMessage($message);
        $props = $kafkaMessage->getProperties();

        if (isset($props['partition']) && is_int($props['partition'])) {
            $kafkaMessage->setPartition($props['partition']);
        }

        if (is_subclass_of($message->getPayload(), Event::class)) {
            $key = $kafkaMessage->getHeader('TraceId') ?: null;
            $kafkaMessage->setKey($key);
        }

        return $kafkaMessage;
    }
}