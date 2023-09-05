<?php

declare(strict_types=1);

namespace FretePago\Core\Infrastructure\MessageBus\Ecotone;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessagePublisher;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\EventBus;
use FretePago\Core\Domain\Message\Message;
use FretePago\Core\Infrastructure\MessageBus\Ecotone\Converters\JsonToPhpConverter;
use FretePago\Core\Infrastructure\MessageBus\MessageBusInterface;
use Psr\Container\ContainerInterface;

class EcotoneLiteMessageBus implements MessageBusInterface
{
    /**
     * @var ConfiguredMessagingSystem
     */
    private $ecotoneInstance = null;

    /**
     * @var array<string>
     */
    private $namespaces = ['Frete\\Core'];

    /**
     * @var ContainerInterface| array<object>
     */
    private $servicesProvidersInstance = [];

    /**
     * @var string
     */
    private $serviceName = 'App';

    public function __construct()
    {
    }

    /**
     * Set the container or array of available services
     *
     * @param ContainerInterface|array $servicesProvidersInstance
     *
     * @return self
     */
    public function withAvailableServices(ContainerInterface|array $servicesProvidersInstance): self
    {
        $this->servicesProvidersInstance = $servicesProvidersInstance;
        return $this;
    }

    /**
     * Set the namespaces for lib analyze annotations
     *
     * @param array $namespaces
     *
     * @return self
     */
    public function withNamespaces(array $namespaces): self
    {
        $this->namespaces = array_merge($this->namespaces, $namespaces);
        return $this;
    }

    /**
     * Set the service name
     *
     * @param string $namespaces
     *
     * @return self
     */
    public function withServiceName(string $name): self
    {
        $this->serviceName = $name;
        return $this;
    }

    /**
     * start the service
     *
     * @return self
     */
    public function run(): self
    {
        $this->ecotoneInstance = EcotoneLite::bootstrap(
            classesToResolve: [JsonToPhpConverter::class],
            containerOrAvailableServices: $this->servicesProvidersInstance,
            configuration: ServiceConfiguration::createWithDefaults()
                ->withFailFast(false)
                ->withNamespaces($this->namespaces)
                ->withDefaultSerializationMediaType(MediaType::APPLICATION_JSON)
                ->withServiceName($this->serviceName)
        );

        return $this;
    }

    /**
     * return original instance lib
     *
     * @return ConfiguredMessagingSystem
     */
    public function getRawBus(): ConfiguredMessagingSystem
    {
        return $this->ecotoneInstance;
    }

    /**
     * return the command bus
     *
     * @return \Ecotone\Modelling\CommandBus
     */
    public function getCommandBus()
    {
        return $this->ecotoneInstance->getCommandBus();
    }

    /**
     * return the query bus
     *
     * @return \Ecotone\Modelling\QueryBus
     */
    public function getQueryBus()
    {
        return $this->ecotoneInstance->getQueryBus();
    }

    /**
     * return the event bus
     *
     * @return \Ecotone\Modelling\EventBus
     */
    public function getEventBus()
    {
        return $this->ecotoneInstance->getEventBus();
    }

    /**
     * send command
     *
     * @param Message $command
     *
     * @return mixed
     */
    public function sendCommand(Message $message)
    {
        return $this->getCommandBus()->send($message);
    }

    /**
     * send query
     *
     * @param Message $query
     *
     * @return mixed
     */
    public function sendQuery(Message $message)
    {
        return $this->getQueryBus()->send($message);
    }

    /**
     * publish event
     *
     * @param Message $message
     *
     * @param string $eventBusReferenceName
     *
     * @return void
     */
    public function publishMessage(Message $message, ?string $eventBusReferenceName = null)
    {
        $eventBusInstance = $this->ecotoneInstance->getDistributedBus();

        if ($eventBusReferenceName)
            $eventBusInstance = $this->ecotoneInstance->getGatewayByName($eventBusReferenceName);

        $eventRoute = $this->getRouteEvent($message);
        // TODO: get message metadata for send
        if ($eventBusInstance instanceof EventBus) {
            $eventBusInstance->publishWithRouting($eventRoute, $message);
        }
        if ($eventBusInstance instanceof DistributedBus) {
            $eventBusInstance->convertAndPublishEvent($eventRoute, $message, []);
        }
        if ($eventBusInstance instanceof MessagePublisher) {
            $eventBusInstance->convertAndSendWithMetadata($message, []);
        }
    }

    private function getRouteEvent($message): string
    {
        $messageNamespace = $message::class;
        return $messageNamespace;
    }

    public function listConsumersCommand(): array
    {
        $commandResult = $this->ecotoneInstance->runConsoleCommand('ecotone:list', []);
        return ['header' => $commandResult->getColumnHeaders(), 'rows' => $commandResult->getRows()];
    }

    public function runConsumerCommand(string $channelName, string $verbose = 'vvv')
    {
        return $this->ecotoneInstance->runConsoleCommand('ecotone:run', ['consumerName' => $channelName, 'verbose' => $verbose]);
    }
}