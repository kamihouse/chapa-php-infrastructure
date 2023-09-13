<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration;

final class KafkaTopicConfiguration
{
    private ?int $publisherPartition = null;
    private ?string $publisherKey = null;
    private array $consumerPartitions = [];

    public function getpublisherPartition(): ?int
    {
        return $this->publisherPartition;
    }

    public function setpublisherPartition(int $publisherPartition): self
    {
        $this->publisherPartition = $publisherPartition;

        return $this;
    }

    public function getPublisherKey(): ?string
    {
        return $this->publisherKey;
    }

    public function setpublisherKey(string $publisherKey): self
    {
        $this->publisherKey = $publisherKey;

        return $this;
    }

    public function getConsumerPartitions(): array
    {
        return $this->consumerPartitions;
    }

    public function setConsumerPartitions(array $consumerPartitions): self
    {
        $this->consumerPartitions = $consumerPartitions;

        return $this;
    }
}
