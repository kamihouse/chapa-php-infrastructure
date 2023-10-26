<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Connection\KafkaConnectionFactory;
use Ecotone\Enqueue\{CachedConnectionFactory, HttpReconnectableConnectionFactory, InboundMessageConverter};
use Ecotone\Enqueue\EnqueueInboundChannelAdapter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Endpoint\InboundChannelAdapterEntrypoint;
use Enqueue\RdKafka\Serializer;
use GuzzleHttp\Exception\ConnectException;

final class KafkaInboundChannelAdapter extends EnqueueInboundChannelAdapter
{
    private $connection;
    private bool $initialized = false;

    public function __construct(
        KafkaConnectionFactory $connectionFactory,
        InboundChannelAdapterEntrypoint $entrypointGateway,
        bool $declareOnStartup,
        string $queueName,
        int $receiveTimeoutInMilliseconds,
        InboundMessageConverter $inboundMessageConverter,
        ConversionService $conversionService,
        protected ?Serializer $customSerializer = null
    ) {
        $this->connection = $connectionFactory;
        parent::__construct(
            CachedConnectionFactory::createFor(new HttpReconnectableConnectionFactory($connectionFactory)),
            $entrypointGateway,
            $declareOnStartup,
            $queueName,
            $receiveTimeoutInMilliseconds,
            $inboundMessageConverter,
            $conversionService
        );
    }

    public function initialize(): void
    {
        $context = $this->connectionFactory->createContext();
        $context->createQueue($this->queueName);
        if ($this->customSerializer != null) {
            $context->setSerializer($this->customSerializer);
        }
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
}
