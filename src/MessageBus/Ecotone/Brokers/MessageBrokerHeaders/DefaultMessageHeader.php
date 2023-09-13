<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders;

use Ramsey\Uuid\Uuid;

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
        if (!isset($this->headers[$key])) {
            return null;
        }

        return $this->headers[$key];
    }

    public function enrichHeadersByArray(array $headers): self
    {
        if (!empty($headers)) {
            foreach ($headers as $key => $value) {
                $this->appendHeader($key, $value);
            }
        }

        return $this;
    }

    private function generateTraceId()
    {
        return Uuid::uuid4()->toString();
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
