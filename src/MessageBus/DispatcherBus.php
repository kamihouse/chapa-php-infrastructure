<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus;

use ChapaPhp\Application\Error\DispatcherError;
use ChapaPhp\Application\Result;
use ChapaPhp\Application\{Command, Dispatcher, Query};
use ChapaPhp\Domain\{Event, Message};

/**
 * @implements Dispatcher<Event|Message, Result>
 */
class DispatcherBus implements Dispatcher
{
    private ?string $eventBusReferenceName = null;

    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    /**
     * Specify the publisher reference to a specific message publisher.
     */
    public function withPublisherBusReferenceName(string $eventBusReferenceName): self
    {
        $this->eventBusReferenceName = $eventBusReferenceName;

        return $this;
    }

    public function dispatch($message)
    {
        try {
            if (is_a($message, Command::class)) {
                return $this->messageBus->sendCommand($message);
            }

            if (is_a($message, Query::class)) {
                return $this->messageBus->sendQuery($message);
            }

            if (is_a($message, Event::class)) {
                $this->messageBus->publishMessage($message, $this->eventBusReferenceName);

                Result::success(true);
            }
        } catch (\Throwable $e) {
            return Result::failure(new DispatcherError($e->getMessage()));
        }

        return Result::failure(new DispatcherError('Message not supported.'));
    }
}
