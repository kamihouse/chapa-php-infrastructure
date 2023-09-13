<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Sqs;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders\DefaultMessageHeader;
use Ecotone\Enqueue\EnqueueMessageChannelBuilder;
use Ecotone\Sqs\SqsInboundChannelAdapterBuilder;
use Enqueue\Sqs\SqsConnectionFactory;

final class CustomSqsBackedMessageChannelBuilder extends EnqueueMessageChannelBuilder
{
    private function __construct(string $channelName, string $connectionReferenceName, string $messageBrokerHeadersReferenceName)
    {
        parent::__construct(
            SqsInboundChannelAdapterBuilder::createWith(
                $channelName,
                $channelName,
                null,
                $connectionReferenceName
            ),
            CustomSqsOutboundChannelAdapterBuilder::create(
                $channelName,
                $connectionReferenceName,
                $messageBrokerHeadersReferenceName
            )
        );
    }

    public static function create(string $channelName, string $connectionReferenceName = SqsConnectionFactory::class, string $messageBrokerHeadersReferenceName = DefaultMessageHeader::class): self
    {
        return new self($channelName, $connectionReferenceName, $messageBrokerHeadersReferenceName);
    }
}
