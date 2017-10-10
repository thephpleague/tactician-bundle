<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder;

use League\Tactician\Bundle\DependencyInjection\DuplicatedCommandBusId;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;
use League\Tactician\Bundle\DependencyInjection\InvalidCommandBusId;
use ArrayIterator;

final class BusBuilders implements \IteratorAggregate
{
    /**
     * @var BusBuilder[]
     */
    private $busBuilders = [];

    /**
     * @var string
     */
    private $defaultBusId;

    public function __construct(array $busBuilders, string $defaultBusId)
    {
        foreach ($busBuilders as $builder) {
            $this->add($builder);
        }

        $this->assertValidBusId($defaultBusId);
        $this->defaultBusId = $defaultBusId;
    }

    public function createBlankRouting(): Routing
    {
        return new Routing(array_keys($this->busBuilders));
    }

    public function defaultBus(): BusBuilder
    {
        return $this->get($this->defaultBusId);
    }

    private function get(string $busId): BusBuilder
    {
        $this->assertValidBusId($busId);

        return $this->busBuilders[$busId];
    }

    /**
     * @return ArrayIterator|BusBuilder[]
     */
    public function getIterator()
    {
        return new ArrayIterator($this->busBuilders);
    }

    private function assertValidBusId($busId)
    {
        if (!isset($this->busBuilders[$busId])) {
            throw InvalidCommandBusId::ofName($busId, array_keys($this->busBuilders));
        }
    }

    private function add(BusBuilder $builder)
    {
        $id = $builder->id();

        if (isset($this->busBuilders[$id])) {
            throw DuplicatedCommandBusId::withId($id);
        }

        $this->busBuilders[$id] = $builder;
    }
}
