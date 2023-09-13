<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Converters;

use ChapaPhp\Domain\Creator\Factory;
use Ecotone\Messaging\Attribute\MediaTypeConverter;
use Ecotone\Messaging\Conversion\{Converter, MediaType};
use Ecotone\Messaging\Handler\TypeDescriptor;

#[MediaTypeConverter]
class JsonToPhpConverter implements Converter
{
    public function __construct(private Factory $factory)
    {
    }

    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWith(MediaType::createApplicationJson())
            && $targetMediaType->isCompatibleWith(MediaType::createApplicationXPHP());
    }

    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        $data = json_decode($source, true, 512, JSON_THROW_ON_ERROR);
        if ($targetType->isClassNotInterface()) {
            $eventType = $targetType->getTypeHint();

            return $this->factory->create($eventType, $data);
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
