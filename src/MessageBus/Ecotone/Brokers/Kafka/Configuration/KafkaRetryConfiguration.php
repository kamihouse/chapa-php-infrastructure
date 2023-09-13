<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Brokers\Kafka\Configuration;

use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Modelling\Config\InstantRetry\InstantRetryConfiguration;

class KafkaRetryConfiguration
{
    #[ServiceContext]
    public function enableRetryConfiguration()
    {
        $retries = getenv('ECOTONE_KAFKA_CONSUMER_ERROR_MAX_RETRY') ? (int) getenv('ECOTONE_KAFKA_CONSUMER_ERROR_MAX_RETRY') : 2;
        $enabled = in_array(getenv('ECOTONE_KAFKA_CONSUMER_ERROR_RETRY_ENABLED'), ['true', '1']);

        return InstantRetryConfiguration::createWithDefaults()
            ->withAsynchronousEndpointsRetry($enabled, $retries)
        ;
    }
}
