<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Connection;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use RdKafka\{Conf, KafkaConsumer};
use Enqueue\RdKafka\Serializer;

class KafkaConnectionFactory extends RdKafkaConnectionFactory
{
    private ?Conf $config = null;
    private ?KafkaConsumer $consumer = null;
    private ?Serializer $messageSerializer = null;
    public function __construct(?array $config = [])
    {
        parent::__construct($config);
    }

    public function withMessageSerializer(Serializer $messageSerializer): self
    {
        $this->messageSerializer = $messageSerializer;
        return $this;
    }

    public function getMessageSerializer(): Serializer|null
    {
        return $this->messageSerializer;
    }
}
