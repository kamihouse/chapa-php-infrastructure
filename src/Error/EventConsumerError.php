<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\Error;

use ChapaPhp\Domain\Event;

class EventConsumerError extends Event
{
    public function __construct(
        public readonly string $id,
        public readonly array $headers,
        public readonly array $payload = [],
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
