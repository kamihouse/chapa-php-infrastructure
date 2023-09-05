<?php

declare(strict_types=1);

namespace FretePago\Core\Infrastructure\MessageBus;

use FretePago\Core\Application\{Command, Dispatcher, Query};
use FretePago\Core\Application\Result;
use FretePago\Core\Domain\Message\Message;
use FretePago\Core\Infrastructure\Errors\InfrastructureError;

class DispatcherBus implements Dispatcher
{
    private ?string $eventBusReferenceName = null;

    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    /**
     * Specify the publisher reference to a specific message publisher
     *
     * @param string $message
     *
     * @return void
     */
    public function withPublisherBusReferenceName(string $eventBusReferenceName): self
    {
        $this->eventBusReferenceName = $eventBusReferenceName;

        return $this;
    }

    /**
     * Dispatch a message or subtype of a message(query, command, event)
     *
     * @param Message $message
     *
     * @return Result
     */
    public function dispatch(Message $message): Result
    {
        try {
            if (is_a($message, Command::class)) {
                return $this->messageBus->sendCommand($message);
            }

            if (is_a($message, Query::class)) {
                return $this->messageBus->sendQuery($message);
            }

            if (is_a($message, Message::class)) {
                $this->messageBus->publishMessage($message, $this->eventBusReferenceName);

                Result::success(true);
            }

        } catch (\Throwable $e) {
            return Result::failure(new InfrastructureError($e->getMessage()));
        }

        return Result::failure(new InfrastructureError('Message not supported.'));
    }

}