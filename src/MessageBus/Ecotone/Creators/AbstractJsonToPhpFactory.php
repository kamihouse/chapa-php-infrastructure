<?php

declare(strict_types=1);

namespace ChapaPhp\Infrastructure\MessageBus\Ecotone\Creators;

use ChapaPhp\Domain\Creator\AbstractFactory;
use ChapaPhp\Domain\Creator\Director;
use Ds\Map;
use Frete\Core\Domain\Errors\NotFoundError;

class AbstractJsonToPhpFactory extends AbstractFactory
{
    /**
     * @param Map<string, Director> $directors
     */
    public function __construct(
        private readonly Map $directors = new Map(),
    ) {
    }

    public function getFactoryTargets(): array
    {
        return $this->directors->keys()->toArray();
    }

    /**
     * @template T
     * @template D
     *
     * @param class-string<T> $target
     * @param D               $data
     *
     * @return T
     */
    public function create($target, $data)
    {
        if (!$this->targetExists($target)) {
            throw new NotFoundError('message target not found');
        }

        /** @var Director<T, D> $director */
        $director = $this->directors->get($target);

        return $director->make($data);
    }

    public function addDirector(Director $director): void
    {
        $this->directors->put($director->targetType(), $director);
    }

    private function targetExists($target): bool
    {
        return in_array($target, $this->getFactoryTargets());
    }
}
