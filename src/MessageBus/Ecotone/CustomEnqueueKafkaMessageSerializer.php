<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders\DefaultMessageHeader;
use Enqueue\RdKafka\RdKafkaMessage;
use Enqueue\RdKafka\Serializer;

class CustomEnqueueKafkaMessageSerializer implements Serializer
{
    public function toString(RdKafkaMessage $message): string
    {
        $this->normalizeMessageToSend($message);
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
        $message = $this->normalizeMessageToReceive($data);
        return $message;
    }

    private function normalizeMessageToSend(RdKafkaMessage $message): void
    {
        $messageProperties = $message->getProperties();
        $headers = [];
        if (!empty($messageProperties['headers'])) {
            $headers = $this->normalizeHeaders($messageProperties['headers']);
            $message->setHeaders($headers);
            unset($messageProperties['headers']);
        }

        if (isset($messageProperties['partition']) && is_int($messageProperties['partition'])) {
            $message->setPartition($messageProperties['partition']);
            unset($messageProperties['partition']);
        }

        if (isset($headers['TraceId']) && !empty($headers['TraceId'])) {
            $message->setKey($headers['TraceId']);
        }

        $message->setProperties($messageProperties);
    }

    private function normalizeHeaders(string $headers): array
    {
        $data = json_decode($headers, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The malformed json given. Error %s and message %s',
                    json_last_error(),
                    json_last_error_msg()
                )
            );
        }
        $defaultMessageHeaders = (new DefaultMessageHeader())
            ->enrichHeadersByArray($data)
            ->getSchema();

        return $defaultMessageHeaders;
    }

    private function normalizeMessageToReceive(array $message): RdKafkaMessage
    {
        if (!empty($message['body'])) {
            $message['data'] = $message['body'];
        }

        if (!is_string($message['data'])) {
            $message['data'] = json_encode($message['data']);
        }

        $properties = ['contentType' => 'application/json'];

        if (!empty($message['properties'])) {
            $properties = array_merge($properties, $message['properties']);
        }

        if (!empty($message['headers'])) {
            $payload = json_decode($message['data'], true);
            $payload['messageHeader'] = $message['headers'];
            $message['data'] = (json_encode($payload));
        }

        return new RdKafkaMessage($message['data'], $properties, $message['headers'] ?? []);
    }
}
