<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\KafkaOutboundChannelAdapterBuilder;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\{ExtensionObjectResolver, NoExternalConfigurationModule};
use Ecotone\Messaging\Config\{Configuration, ModuleReferenceSearchService, ServiceConfiguration};
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\{GatewayHeaderBuilder, GatewayHeaderValueBuilder, GatewayHeadersBuilder, GatewayPayloadBuilder};
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\{MessageHeaders, MessagePublisher};

#[ModuleAnnotation]
final class KafkaMessagePublisherModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $serviceConfiguration = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults());

        /** @var KafkaMessagePublisherConfiguration $messagePublisher */
        foreach (ExtensionObjectResolver::resolve(KafkaMessagePublisherConfiguration::class, $extensionObjects) as $messagePublisher) {
            $mediaType = $messagePublisher->getOutputDefaultConversionMediaType() ?: $serviceConfiguration->getDefaultSerializationMediaType();
            $messagingConfiguration
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($messagePublisher->getReferenceName(), MessagePublisher::class, 'send', $messagePublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create('data'),
                            GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($messagePublisher->getReferenceName(), MessagePublisher::class, 'sendWithMetadata', $messagePublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create('data'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($messagePublisher->getReferenceName(), MessagePublisher::class, 'convertAndSend', $messagePublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create('data'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP),
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($messagePublisher->getReferenceName(), MessagePublisher::class, 'convertAndSendWithMetadata', $messagePublisher->getReferenceName())
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create('data'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP),
                        ])
                )
                ->registerMessageHandler(
                    KafkaOutboundChannelAdapterBuilder::create($messagePublisher->getQueueName(), $messagePublisher->getConnectionReference(), $messagePublisher->getmessageBrokerHeadersReferenceName(), $messagePublisher->getKafkaTopicConfiguration())
                        ->withEndpointId($messagePublisher->getReferenceName() . '.handler')
                        ->withInputChannelName($messagePublisher->getReferenceName())
                        ->withAutoDeclareOnSend($messagePublisher->isAutoDeclareOnSend())
                        ->withHeaderMapper($messagePublisher->getHeaderMapper())
                        ->withDefaultConversionMediaType($mediaType)
                )
            ;
        }
    }

    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof KafkaMessagePublisherConfiguration
            || $extensionObject instanceof ServiceConfiguration;
    }

    public function getModulePackageName(): string
    {
        return 'kafka';
    }
}
