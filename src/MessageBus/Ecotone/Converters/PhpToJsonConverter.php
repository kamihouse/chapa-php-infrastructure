<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Converters;

use Ecotone\Messaging\Attribute\MediaTypeConverter;
use Ecotone\Messaging\Conversion\{Converter, MediaType};
use Ecotone\Messaging\Handler\TypeDescriptor;

#[MediaTypeConverter]
class PhpToJsonConverter implements Converter
{
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWith(MediaType::createApplicationXPHP())
            && ($targetMediaType->isCompatibleWith(MediaType::createApplicationJson())
                || $targetMediaType->isCompatibleWith(MediaType::createTextPlain()));
    }

    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        if ($sourceType->isClassNotInterface()) {
            $source = get_object_vars($source);
        }

        if (is_object($source)) {
            $source = (array) $source;
        }

        return json_encode($source, JSON_THROW_ON_ERROR);
    }
}
