<?php

declare(strict_types=1);

namespace FretePago\Core\Infrastructure\MessageBus\Ecotone\Converters;

use Ecotone\Messaging\Attribute\MediaTypeConverter;
use Ecotone\Messaging\Conversion\{Converter, MediaType};
use Ecotone\Messaging\Handler\TypeDescriptor;
use FretePago\Core\Application\IntegrationEvent;

#[MediaTypeConverter]
class JsonToPhpConverter implements Converter
{
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWith(MediaType::createApplicationJson())
            && $targetMediaType->isCompatibleWith(MediaType::createApplicationXPHP());
    }

    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        $data = json_decode($source, true, 512, JSON_THROW_ON_ERROR);
        if ($targetType->isClassNotInterface()) {
            $commandType = $targetType->getTypeHint();
            $dataParams = $data['data'];
            $instance = new $commandType(...$dataParams);
            /* if (is_subclass_of($instance, IntegrationEvent::class) && !empty($data['messageHeader'])) {
                $instance->setMessageHeader($data['messageHeader']);
            } */
            return $instance;
        }
        if ($targetType->isNonCollectionArray()) {
            return $data;
        }
        if ($targetType->isCompoundObjectType()) {
            return json_decode(json_encode($data), false);
        }

        return $source;
    }
}