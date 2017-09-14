<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder;

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
            $this->busBuilders[$builder->id()] = $builder;
        }

        $this->defaultBusId = $defaultBusId;
    }

    public function getBusIds(): array
    {
        return array_keys($this->busBuilders);
    }

    public function defaultBus(): BusBuilder
    {
        return $this->get($this->defaultBusId);
    }

    private function get(string $busId): BusBuilder
    {
        if (!isset($this->busBuilders[$busId])) {
            throw new \InvalidArgumentException("Invalid bus id '$busId'. Valid buses are: " . implode(', ', array_keys($this->busBuilders)));
        }

        return $this->busBuilders[$busId];
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->busBuilders);
    }
}