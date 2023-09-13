<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders;

interface IHeaderMessage
{
    public function getSchema(): array;

    public function setHeaders(array $headers): self;

    public function appendHeader(string $key, mixed $value): self;

    public function getHeader(string $key);

    public function enrichHeadersByArray(array $headers): self;
}
