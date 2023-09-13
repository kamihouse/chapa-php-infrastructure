<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\Error;

class InfrastructureError extends \Exception
{
    public function __construct(
        string $message,
        int $code = 3,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
