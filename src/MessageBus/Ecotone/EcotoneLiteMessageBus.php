<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Converters\JsonToPhpConverter;
use ChapaPhp\Infrastructure\MessageBus\MessageBusInterface;
use Ds\Map;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessagePublisher;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;
use Psr\Container\ContainerInterface;

class EcotoneLiteMessageBus implements MessageBusInterface
{
    /**
     * @var ConfiguredMessagingSystem
     */
    private $ecotoneInstance;

    /**
     * @var array<string>
     */
    private $namespaces = ['ChapaPhp'];

    /**
     * @var array<object>|ContainerInterface
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
     * Set the container or array of available services.
     */
    public function withAvailableServices(ContainerInterface|array $servicesProvidersInstance): self
    {
        $this->servicesProvidersInstance = $servicesProvidersInstance;

        return $this;
    }

    /**
     * Set the namespaces for lib analyze annotations.
     */
    public function withNamespaces(array $namespaces): self
    {
        $this->namespaces = array_merge($this->namespaces, $namespaces);

        return $this;
    }

    /**
     * Set the service name.
     */
    public function withServiceName(string $name): self
    {
        $this->serviceName = $name;

        return $this;
    }

    /**
     * start the service.
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
     * return the raw bus.
     *
     * @implements MessageBusInterface<ConfiguredMessagingSystem>
     *
     * @return ConfiguredMessagingSystem
     */
    public function getRawBus()
    {
        return $this->ecotoneInstance;
    }

    /**
     * return the command bus.
     *
     * @implements MessageBusInterface<CommandBus>
     *
     * @return CommandBus
     */
    public function getCommandBus()
    {
        return $this->ecotoneInstance->getCommandBus();
    }

    /**
     * return the query bus.
     *
     * @implements MessageBusInterface<QueryBus>
     *
     * @return QueryBus
     */
    public function getQueryBus()
    {
        return $this->ecotoneInstance->getQueryBus();
    }

    /**
     * return the event bus.
     *
     * @implements MessageBusInterface<EventBus>
     *
     * @return EventBus
     */
    public function getEventBus()
    {
        return $this->ecotoneInstance->getEventBus();
    }

    /**
     * send command.
     *
     * @template T
     * @template R
     *
     * @implements MessageBusInterface<T>
     *
     * @param T
     *
     * @return R
     */
    public function sendCommand($message)
    {
        return $this->getCommandBus()->send($message);
    }

    /**
     * send Query.
     *
     * @template T
     * @template R
     *
     * @implements MessageBusInterface<T>
     *
     * @param T
     *
     * @return R
     */
    public function sendQuery($message)
    {
        return $this->getQueryBus()->send($message);
    }

    /**
     * publish event.
     *
     * @template T
     *
     * @implements MessageBusInterface<T>
     *
     * @param T $message
     */
    public function publishMessage($message, ?string $eventBusReferenceName = null)
    {
        if (null != $eventBusReferenceName) {
            $eventBusInstance = $this->ecotoneInstance->getGatewayByName($eventBusReferenceName);
        } else {
            $eventBusInstance = $this->ecotoneInstance->getDistributedBus();
        }

        $payload = is_a($message->payload(), Map::class) ? $message->payload()->toArray() : $message->payload();
        $headers = ['headers' => is_a($message->headers(), Map::class) ? $message->headers()->toArray() : $message->headers()];
        $eventRoute = $this->getRouteEvent($message);
        if ($eventBusInstance instanceof EventBus) {
            $eventBusInstance->publishWithRouting(routingKey: $eventRoute, event: $payload, metadata: $headers);
        }
        if ($eventBusInstance instanceof DistributedBus) {
            $eventBusInstance->convertAndPublishEvent(routingKey: $eventRoute, event: $payload, metadata: $headers);
        }
        if ($eventBusInstance instanceof MessagePublisher) {
            $eventBusInstance->convertAndSendWithMetadata(data: $payload, metadata: $payload);
        }
    }

    private function getRouteEvent($message): string
    {
        return $message::class;
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
