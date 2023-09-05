<?php

declare(strict_types=1);

namespace FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers;

use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessage;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessageConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\{Message, MessageHandler, MessageHeaders};
use FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders\IHeaderMessage;
use Interop\Queue\{Destination, Message as buildMessageReturn};

abstract class CustomEnqueueOutboundChannelAdapter implements MessageHandler
{
    private OutboundMessage $outboundMessage;
    private bool $initialized = false;

    public function __construct(
        protected CachedConnectionFactory $connectionFactory,
        protected Destination $destination,
        protected bool $autoDeclare,
        protected OutboundMessageConverter $outboundMessageConverter,
        protected ConversionService $conversionService,
        private IHeaderMessage $messageBrokerHeaders
    ) {
    }

    abstract public function initialize(): void;

    public function handle(Message $message): void
    {
        if ($this->autoDeclare && !$this->initialized) {
            $this->initialize();
            $this->initialized = true;
        }

        $messageToSend = $this->buildMessage($message);
        $this->connectionFactory->getProducer()
            ->setTimeToLive($this->outboundMessage->getTimeToLive())
            ->setDeliveryDelay($this->outboundMessage->getDeliveryDelay())
            ->send($this->destination, $messageToSend)
        ;
    }

    protected function buildMessage(Message $message): buildMessageReturn
    {
        $this->outboundMessage = $outboundMessage = $this->outboundMessageConverter->prepare($message, $this->conversionService);
        $headers = $outboundMessage->getHeaders();
        $headers[MessageHeaders::CONTENT_TYPE] = $outboundMessage->getContentType();

        if (is_subclass_of($message->getPayload(), \FretePago\Core\Domain\Event::class)) {
            $this->messageBrokerHeaders->enrichHeaderByMessagePayload($message->getPayload());
        }

        $this->messageBrokerHeaders->enrichHeadersByArray($headers);

        $messageBrokerHeaders = $this->messageBrokerHeaders->getSchema();

        return $this->connectionFactory->createContext()->createMessage($outboundMessage->getPayload(), $headers, $messageBrokerHeaders);
    }
}