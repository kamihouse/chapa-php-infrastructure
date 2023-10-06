<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration;

use Enqueue\RdKafka\RdKafkaMessage;
use Enqueue\RdKafka\Serializer;

class KafkaEnqueueJsonSerializable implements Serializer
{
    public function toString(RdKafkaMessage $message): string
    {
        $json = json_encode([
            'data' => $message->getBody(),
            'properties' => $message->getProperties(),
            'headers' => $message->getHeaders(),
        ]);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The malformed json given. Error %s and message %s',
                    json_last_error(),
                    json_last_error_msg()
                )
            );
        }

        return $json;
    }

    public function toMessage(string $string): RdKafkaMessage
    {
        $data = json_decode($string, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The malformed json given. Error %s and message %s',
                    json_last_error(),
                    json_last_error_msg()
                )
            );
        }

        if (!empty($data['body'])) {
            $data['data'] = $data['body'];
        }

        if (!is_string($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }

        $properties = ['contentType' => 'application/json'];

        if (!empty($data['properties'])) {
            $properties = array_merge($properties, $data['properties']);
        }

        return new RdKafkaMessage($data['data'], $properties, $data['headers'] ?? []);
    }
}
