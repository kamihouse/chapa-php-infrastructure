<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\CustomEnqueueInboundChannelAdapter;
use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration\KafkaEnqueueJsonSerializable;
use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration\KafkaTopicConfiguration;
use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Connection\KafkaConnectionFactory;
use Ecotone\Enqueue\{CachedConnectionFactory, HttpReconnectableConnectionFactory, InboundMessageConverter};
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Endpoint\InboundChannelAdapterEntrypoint;
use GuzzleHttp\Exception\ConnectException;
use Ecotone\Messaging\Message;

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

    public function receiveMessage(int $timeout = 0): ?Message
    {
        try {
            if ($this->declareOnStartup && false === $this->initialized) {
                $this->initialize();

                $this->initialized = true;
            }

            /** @var \Enqueue\RdKafka\RdKafkaContext */
            $context = $this->connectionFactory->createContext();
            $context->setSerializer(new KafkaEnqueueJsonSerializable());

            /** @var Consumer */
            $consumer = $this->connectionFactory->getConsumer(
                $context->createQueue($this->queueName)
            );

            /** @var array|bool $consumableParamsMessage */
            $consumableParamsMessage = $this->getMessageParamsConsume();
            if (!$consumableParamsMessage) {
                return null;
            }

            if (is_array($consumableParamsMessage) && !in_array($consumableParamsMessage['partition'], $this->activeConsumerPartitions)) {
                $consumer->getQueue()->setPartition($consumableParamsMessage['partition']);
                $consumer->setOffset($consumableParamsMessage['offset']);
                $this->activeConsumerPartitions[] = $consumableParamsMessage['partition'];
            }

            /** @var ?EnqueueMessage $message */
            $message = $consumer->receive($timeout ?: $this->receiveTimeoutInMilliseconds);
            if (!$message) {
                return null;
            }

            if (!empty($message->getHeaders())) {
                $payload = json_decode($message->getBody(), true);
                $payload['messageHeader'] = $message->getHeaders();
                $message->setBody(json_encode($payload));
            }

            $convertedMessage = $this->inboundMessageConverter->toMessage($message, $consumer, $this->conversionService);
            $convertedMessage = $this->enrichMessage($message, $convertedMessage);

            return $convertedMessage->build();
        } catch (\Exception $exception) {
            // @phpstan-ignore-next-line
            if ($this->isConnectionException($exception) || ($exception->getPrevious() && $this->isConnectionException($exception->getPrevious()))) {
                throw new ConnectionException('There was a problem while polling message channel', 0, $exception);
            }

            throw $exception;
        }
    }
}
