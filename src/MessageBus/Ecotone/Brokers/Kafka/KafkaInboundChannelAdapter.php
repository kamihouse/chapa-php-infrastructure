<?php

declare(strict_types=1);

namespace FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers\Kafka;

use Ecotone\Enqueue\{CachedConnectionFactory, HttpReconnectableConnectionFactory, InboundMessageConverter};
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Endpoint\InboundChannelAdapterEntrypoint;
use FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers\CustomEnqueueInboundChannelAdapter;
use FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration\KafkaTopicConfiguration;
use FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Connection\KafkaConnectionFactory;
use GuzzleHttp\Exception\ConnectException;

final class KafkaInboundChannelAdapter extends CustomEnqueueInboundChannelAdapter
{
    private $connection;

    public function __construct(
        KafkaConnectionFactory $connectionFactory,
        InboundChannelAdapterEntrypoint $entrypointGateway,
        bool $declareOnStartup,
        string $queueName,
        int $receiveTimeoutInMilliseconds,
        InboundMessageConverter $inboundMessageConverter,
        ConversionService $conversionService,
        private ?KafkaTopicConfiguration $topicConfig = null,
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
    }

    public function connectionException(): string
    {
        return ConnectException::class;
    }

    protected function getMessageParamsConsume(): array|bool
    {
        if (!$this->topicConfig || empty($this->topicConfig->getConsumerPartitions())) {
            return true;
        }

        $kafkaConsumer = $this->connection->getConsumer('false');
        $topicPartitions = array_map(
            fn($val) => $this->connection->createTopicPartition($this->queueName, $val),
            $this->topicConfig->getConsumerPartitions()
        );

        $commitedOfssets = array_map(function ($val) {
            if (-1001 == $val->getOffset()) {
                $val->setOffset(0);
            }

            return $val;
        }, $kafkaConsumer->getCommittedOffsets($topicPartitions, 2000));

        $kafkaConsumer->assign($commitedOfssets);
        $consumedMessage = $kafkaConsumer->consume(2000);
        if (0 != $consumedMessage->err) {
            return false;
        }

        return ['offset' => $consumedMessage->offset, 'partition' => $consumedMessage->partition];
    }
}