<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers;

use ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\MessageBrokerHeaders\IHeaderMessage;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessage;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessageConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Interop\Queue\{Destination};
use Ecotone\Enqueue\EnqueueOutboundChannelAdapter;

abstract class CustomEnqueueOutboundChannelAdapter extends EnqueueOutboundChannelAdapter
{
    private OutboundMessage $outboundMessage;
    private bool $initialized = false;

    public function __construct(
        protected CachedConnectionFactory $connectionFactory,
        protected Destination $destination,
        protected bool $autoDeclare,
        protected OutboundMessageConverter $outboundMessageConverter,
        protected ConversionService $conversionService,
        private IHeaderMessage $messageBrokerHeaders
    ) {
        parent::__construct(
            $connectionFactory,
            $destination,
            $autoDeclare,
            $outboundMessageConverter,
            $conversionService
        );
    }

    abstract public function initialize(): void;
}
