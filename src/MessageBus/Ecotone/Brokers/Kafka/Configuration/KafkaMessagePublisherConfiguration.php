<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Connection\KafkaConnectionFactory;
use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders\DefaultMessageHeader;
use Ecotone\Messaging\MessagePublisher;

final class KafkaMessagePublisherConfiguration
{
    private bool $autoDeclareOnSend = true;
    private string $headerMapper = '';

    private function __construct(private string $connectionReference, private string $queueName, private ?string $outputDefaultConversionMediaType, private string $referenceName, private string $messageBrokerHeadersReferenceName, private ?KafkaTopicConfiguration $topicConfig)
    {
    }

    public static function create(string $publisherReferenceName = MessagePublisher::class, string $queueName = '', ?string $outputDefaultConversionMediaType = null, string $connectionReference = KafkaConnectionFactory::class, string $messageBrokerHeadersReferenceName = DefaultMessageHeader::class, ?KafkaTopicConfiguration $topicConfig = null): self
    {
        return new self($connectionReference, $queueName, $outputDefaultConversionMediaType, $publisherReferenceName, $messageBrokerHeadersReferenceName, $topicConfig);
    }

    public function getConnectionReference(): string
    {
        return $this->connectionReference;
    }

    public function withAutoDeclareQueueOnSend(bool $autoDeclareQueueOnSend): self
    {
        $this->autoDeclareOnSend = $autoDeclareQueueOnSend;

        return $this;
    }

    /**
     * @param string $headerMapper comma separated list of headers to be mapped.
     *                             (e.g. "\*" or "thing1*, thing2" or "*thing1")
     */
    public function withHeaderMapper(string $headerMapper): self
    {
        $this->headerMapper = $headerMapper;

        return $this;
    }

    public function isAutoDeclareOnSend(): bool
    {
        return $this->autoDeclareOnSend;
    }

    public function getHeaderMapper(): string
    {
        return $this->headerMapper;
    }

    public function getOutputDefaultConversionMediaType(): ?string
    {
        return $this->outputDefaultConversionMediaType;
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    public function getKafkaTopicConfiguration(): ?KafkaTopicConfiguration
    {
        return $this->topicConfig;
    }

    public function getmessageBrokerHeadersReferenceName(): string
    {
        return $this->messageBrokerHeadersReferenceName;
    }
}
