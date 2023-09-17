<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\CustomEnqueueOutboundChannelAdapter;
use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders\IHeaderMessage;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessageConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Message;
use Enqueue\RdKafka\RdKafkaTopic;
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
        $headers = $kafkaMessage->getHeaders();
        if (isset($props['partition']) && is_int($props['partition'])) {
            $kafkaMessage->setPartition($props['partition']);
        }

        if (isset($headers['Identifier']) && !empty($headers['Identifier'])) {
            $kafkaMessage->setKey($headers['Identifier']);
        }

        return $kafkaMessage;
    }
}
