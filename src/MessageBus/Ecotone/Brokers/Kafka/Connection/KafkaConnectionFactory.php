<?php

declare(strict_types=1);

namespace FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Connection;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use RdKafka\{Conf, KafkaConsumer, TopicPartition};

class KafkaConnectionFactory extends RdKafkaConnectionFactory
{
    private ?Conf $config = null;
    private ?KafkaConsumer $consumer = null;

    public function __construct(?array $config = [])
    {
        parent::__construct($config);
        $this->setConfig($config);
    }

    public function setConfig(?array $config = null): void
    {
        $this->config ??= new Conf();
        if (isset($config['topic']) && is_array($config['topic'])) {
            foreach ($config['topic'] as $key => $value) {
                $this->config->set($key, $value);
            }
        }

        if (isset($config['partitioner'])) {
            $this->config->set('partitioner', $config['partitioner']);
        }

        if (isset($config['global']) && is_array($config['global'])) {
            foreach ($config['global'] as $key => $value) {
                $this->config->set($key, $value);
            }
        }

        if (isset($config['dr_msg_cb'])) {
            $this->config->setDrMsgCb($config['dr_msg_cb']);
        }

        if (isset($config['error_cb'])) {
            $this->config->setErrorCb($config['error_cb']);
        }

        if (isset($config['rebalance_cb'])) {
            $this->config->setRebalanceCb($config['rebalance_cb']);
        }

        if (isset($config['stats_cb'])) {
            $this->config->setStatsCb($config['stats_cb']);
        }
    }

    public function getConsumer(string $autoCommit = 'true'): KafkaConsumer
    {
        $this->config->set('enable.auto.commit', (string) $autoCommit);
        if (!$this->consumer) {
            $this->consumer = new KafkaConsumer($this->config);
        }

        return $this->consumer;
    }

    public function createTopicPartition(string $queueName, int $partition): TopicPartition
    {
        return new TopicPartition($queueName, $partition);
    }
}