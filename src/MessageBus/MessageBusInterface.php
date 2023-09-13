<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus;

use ChapaPhp\Application\Command;
use ChapaPhp\Application\Query;
use ChapaPhp\Domain\Event;

interface MessageBusInterface
{
    /**
     * @template T
     */
    public function getRawBus();

    /**
     * @template T
     */
    public function getCommandBus();

    /**
     * @template T
     */
    public function getQueryBus();

    /**
     * @template T of Command
     * @template R
     */
    public function sendCommand($message);

    /**
     * @template T of Query
     * @template R
     */
    public function sendQuery($message);

    /**
     * @template T of Event
     */
    public function publishMessage($message, ?string $eventBusReferenceName);

    /**
     * list consumer channels to run.
     */
    public function listConsumersCommand(): array;

    /**
     * @template R
     * run consumer channels
     *
     * @return R
     */
    public function runConsumerCommand(string $channelName, string $verbose = 'vvv');
}
