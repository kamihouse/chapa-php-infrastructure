<?php

declare(strict_types=1);

namespace FretePago\Core\Infrastructure\MessageBus;

use FretePago\Core\Domain\Message\Message;

interface MessageBusInterface
{
    /**
     * return original instance lib
     *
     * @return mixed
     */
    public function getRawBus();

    /**
     * return the command bus
     *
     * @return mixed
     */
    public function getCommandBus();

    /**
     * return the query bus
     *
     * @return mixed
     */
    public function getQueryBus();

    /**
     * send command
     *
     * @param Message $message
     *
     * @return mixed
     */
    public function sendCommand(Message $message);

    /**
     * send query
     *
     * @param Message $message
     *
     * @return mixed
     */
    public function sendQuery(Message $message);

    /**
     * publish event
     *
     * @param Message $message
     *
     * @param string $eventBusReferenceName
     *
     * @return void
     */
    public function publishMessage(Message $message, ?string $eventBusReferenceName);

    /**
     * list consumer channels to run
     *
     * @param Message $message
     *
     * @return array
     */
    public function listConsumersCommand(): array;

    /**
     * run consumer channels
     *
     * @param Message $message
     *
     * @param string $channelName
     *
     * @param string $verbose
     *
     * @return void
     */
    public function runConsumerCommand(string $channelName, string $verbose = 'vvv');

}