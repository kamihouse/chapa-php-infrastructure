<?php

declare(strict_types=1);

namespace FretePago\Core\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders;

use FretePago\Core\Domain\Event;

interface IHeaderMessage
{
    public function getSchema(): array;
    public function setHeaders(array $headers): self;
    public function appendHeader(string $key, mixed $value): self;
    public function getHeader(string $key);
    public function enrichHeaderByMessagePayload(Event $messagePayload): self;
    public function enrichHeadersByArray(array $headers): self;
}