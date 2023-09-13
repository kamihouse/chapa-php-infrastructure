<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\KafkaInboundChannelAdapterBuilder;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\{Configuration, ModuleReferenceSearchService};
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
final class KafkaMessageConsumerModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        /** @var KafkaMessageConsumerConfiguration $extensionObject */
        foreach ($extensionObjects as $extensionObject) {
            $messagingConfiguration->registerConsumer(
                KafkaInboundChannelAdapterBuilder::createWith(
                    $extensionObject->getEndpointId(),
                    $extensionObject->getQueueName(),
                    $extensionObject->getEndpointId(),
                    $extensionObject->getConnectionReferenceName(),
                    $extensionObject->getKafkaTopicConfiguration()
                )
                    ->withDeclareOnStartup($extensionObject->isDeclaredOnStartup())
                    ->withHeaderMapper($extensionObject->getHeaderMapper())
                    ->withReceiveTimeout($extensionObject->getReceiveTimeoutInMilliseconds())
            );
        }
    }

    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof KafkaMessageConsumerConfiguration;
    }

    public function getModulePackageName(): string
    {
        return 'kafka';
    }
}
