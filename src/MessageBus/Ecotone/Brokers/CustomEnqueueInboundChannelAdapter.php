<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers;

use Ecotone\Enqueue\EnqueueInboundChannelAdapter;
use Ecotone\Messaging\Endpoint\PollingConsumer\ConnectionException;
use Ecotone\Messaging\Message;
use Interop\Queue\Consumer;
use Interop\Queue\Message as EnqueueMessage;

abstract class CustomEnqueueInboundChannelAdapter extends EnqueueInboundChannelAdapter
{
    protected bool $initialized = false;
    protected array $activeConsumerPartitions = [];

    public function receiveMessage(int $timeout = 0): ?Message
    {
        try {
            if ($this->declareOnStartup && false === $this->initialized) {
                $this->initialize();

                $this->initialized = true;
            }

            /** @var Consumer */
            $consumer = $this->connectionFactory->getConsumer(
                $this->connectionFactory->createContext()->createQueue($this->queueName)
            );

            /** @var array|bool $consumableParamsMessage */
            $consumableParamsMessage = $this->getMessageParamsConsume();
            if (!$consumableParamsMessage) {
                return null;
            }

            /** @var ?EnqueueMessage $message */
            $message = $consumer->receive($timeout ?: $this->receiveTimeoutInMilliseconds);
            if (!$message) {
                return null;
            }

            if (!empty($message->getHeaders())) {
                $payload = json_decode($message->getBody(), true);
                $payload['messageHeader'] = $message->getHeaders();
                $message->setBody(json_encode($payload));
            }

            $convertedMessage = $this->inboundMessageConverter->toMessage($message, $consumer, $this->conversionService);
            $convertedMessage = $this->enrichMessage($message, $convertedMessage);

            return $convertedMessage->build();
        } catch (\Exception $exception) {
            // @phpstan-ignore-next-line
            if ($this->isConnectionException($exception) || ($exception->getPrevious() && $this->isConnectionException($exception->getPrevious()))) {
                throw new ConnectionException('There was a problem while polling message channel', 0, $exception);
            }

            throw $exception;
        }
    }

    protected function getMessageParamsConsume()
    {
        return true;
    }

    protected function isConnectionException(\Exception $exception): bool
    {
        // @phpstan-ignore-next-line
        return is_subclass_of($exception, $this->connectionException()) || $this->connectionException() === $exception::class;
    }
}
