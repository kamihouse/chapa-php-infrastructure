<?php

declare(strict_types=1);

namespace FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders;

use FretePago\Core\Domain\Event;
use FretePago\Core\Infrastructure\UuidGenerator;

class DefaultMessageHeader implements IHeaderMessage
{
    private array $headers;
    public function __construct()
    {
        $this->headers = $this->defaultHeaders();
    }
    public function getSchema(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }
    public function appendHeader(string $key, mixed $value): self
    {
        $this->headers = array_merge($this->headers, [$key => $value]);
        return $this;
    }

    public function getHeader(string $key)
    {
        if (!isset($this->headers[$key]))
            return null;

        return $this->headers[$key];
    }

    public function enrichHeaderByMessagePayload(Event $messagePayload): self
    {
        if (!empty($messagePayload->getVersion()) && empty($headersBrokerSchema['SchemaVersion'])) {
            $this->appendHeader('SchemaVersion', $messagePayload->getVersion());
        }

        if (!empty($messagePayload->getMessageHeaders())) {
            $this->appendHeader('TraceId', $messagePayload->getMessageHeaders()['TraceId']);
        }

        if (!empty($messagePayload->getOccurredOn()) && is_a($messagePayload->getOccurredOn(), \DateTimeImmutable::class)) {
            $this->appendHeader('Timestamp', $messagePayload->getOccurredOn()->getTimestamp());
        }

        if (!empty($messagePayload->getSchema())) {
            $this->appendHeader('Schema', $messagePayload->getSchema());
        }
        return $this;
    }

    public function enrichHeadersByArray(array $headers): self
    {
        if (!empty($headers['__TypeId__']) && empty($this->getHeader('eventType'))) {
            $transformToEventType = str_replace('\\', '.', $headers['__TypeId__']);
            $this->appendHeader('EventType', $transformToEventType);
        }
        if (!empty($headers['headers'])) {
            foreach (json_decode($headers['headers'], true) as $key => $value) {
                $this->appendHeader($key, $value);
            }
        }
        return $this;
    }

    private function generateTraceId()
    {
        return UuidGenerator::generate();
    }

    private function generateTimestamp()
    {
        return (new \DateTimeImmutable())->getTimestamp();
    }

    private function defaultHeaders(): array
    {
        return [
            'TraceId' => $this->generateTraceId(),
            'Source' => getenv('APP_NAME') ? getenv('APP_NAME') : '',
            'SchemaVersion' => null,
            'Timestamp' => $this->generateTimestamp(),
            'EventType' => null,
        ];
    }
}