<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Sqs;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders\DefaultMessageHeader;
use Ecotone\Enqueue\{CachedConnectionFactory, EnqueueOutboundChannelAdapterBuilder, HttpReconnectableConnectionFactory};
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessageConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\{ChannelResolver, ReferenceSearchService};
use Enqueue\Sqs\SqsConnectionFactory;

class CustomSqsOutboundChannelAdapterBuilder extends EnqueueOutboundChannelAdapterBuilder
{
    private array $staticHeadersToAdd = [];

    private function __construct(private string $queueName, private string $connectionFactoryReferenceName, private string $messageBrokerHeadersReferenceName)
    {
        $this->initialize($connectionFactoryReferenceName);
    }

    public static function create(string $queueName, string $connectionFactoryReferenceName = SqsConnectionFactory::class, string $messageBrokerHeadersReferenceName = DefaultMessageHeader::class): self
    {
        return new self($queueName, $connectionFactoryReferenceName, $messageBrokerHeadersReferenceName);
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): CustomSqsOutboundChannelAdapter
    {
        /** @var SqsConnectionFactory $connectionFactory */
        $connectionFactory = $referenceSearchService->get($this->connectionFactoryReferenceName);

        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);

        // call the headers HERE!
        $messageBrokerHeadersReferenceName = new ($this->messageBrokerHeadersReferenceName)();

        return new CustomSqsOutboundChannelAdapter(
            CachedConnectionFactory::createFor(new HttpReconnectableConnectionFactory($connectionFactory)),
            $this->queueName,
            $this->autoDeclare,
            new OutboundMessageConverter($this->headerMapper, $this->defaultConversionMediaType, $this->defaultDeliveryDelay, $this->defaultTimeToLive, $this->defaultPriority, $this->staticHeadersToAdd),
            $conversionService,
            $messageBrokerHeadersReferenceName
        );
    }
}
