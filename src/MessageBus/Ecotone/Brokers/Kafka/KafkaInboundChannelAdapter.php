<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka;

use Ecotone\Enqueue\{CachedConnectionFactory, InboundMessageConverter};
use Ecotone\Enqueue\EnqueueInboundChannelAdapter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\MessagePoller;
use Ecotone\Messaging\Support\MessageBuilder;
use Enqueue\RdKafka\JsonSerializer;
use Enqueue\RdKafka\RdKafkaContext;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use Interop\Queue\Message as EnqueueMessage;
use Ecotone\Messaging\Message;

final class KafkaInboundChannelAdapter extends EnqueueInboundChannelAdapter
{
    private bool $initialized = false;
    private RdKafkaContext $context;

    public function __construct(
        protected CachedConnectionFactory $connectionFactory,
        protected bool $declareOnStartup,
        protected string $queueName,
        protected int $receiveTimeoutInMilliseconds,
        protected InboundMessageConverter $inboundMessageConverter,
        protected ConversionService $conversionService,
    ) {
    }

    public function initialize(): void
    {
        $this->context = $this->connectionFactory->createContext();
        $this->context->createQueue($this->queueName);
    }

    public function connectionException(): string
    {
        return ConnectException::class;
    }

    protected function isConnectionException(\Exception $exception): bool
    {
        // @phpstan-ignore-next-line
        return is_subclass_of($exception, $this->connectionException()) || $this->connectionException() === $exception::class;
    }

    public function enrichMessage(EnqueueMessage $sourceMessage, MessageBuilder $targetMessage): MessageBuilder
    {
        $serializer = $this->context->getSerializer();
        if ($serializer != null && $serializer instanceof JsonSerializer != true) {
            $this->serializeMessageWithHeaders($targetMessage, $sourceMessage);
        }
        return $targetMessage;
    }

    private function serializeMessageWithHeaders(MessageBuilder $targetMessage, EnqueueMessage $sourceMessage)
    {
        $json = json_encode([
            'data' => $sourceMessage->getBody(),
            'properties' => $sourceMessage->getProperties(),
            'headers' => $sourceMessage->getHeaders(),
        ]);
        $message = $this->context->getSerializer()->toMessage($json);
        $targetMessage->setPayload($message->getBody());
        $targetMessage->setMultipleHeaders($message->getHeaders());

        return $targetMessage;
    }
}
