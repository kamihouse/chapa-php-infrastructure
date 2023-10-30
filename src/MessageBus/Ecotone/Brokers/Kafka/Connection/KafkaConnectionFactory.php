<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Connection;

use Ecotone\Enqueue\ReconnectableConnectionFactory;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\RdKafkaContext;
use Interop\Queue\Context;
use RdKafka\{Conf, KafkaConsumer};
use Enqueue\RdKafka\Serializer;

class KafkaConnectionFactory extends RdKafkaConnectionFactory
{
    private RdKafkaConnectionFactory $connection;
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

    public function createContext(): Context
    {
        $context = parent::createContext();
        if ($this->messageSerializer)
            $context->setSerializer($this->messageSerializer);

        return $context;
    }
}
