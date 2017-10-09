<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection;

use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilder;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilders;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;

class RoutingDebugReport
{
    /**
     * @var array
     */
    private $report;

    public function __construct(BusBuilders $builders, Routing $routing)
    {
        $this->report = [];

        /** @var BusBuilder $builder */
        foreach ($builders->getIterator() as $builder) {
            $mapping = $routing->commandToServiceMapping($builder->id());
            $this->report[$builder->id()] = $this->formatMapping($mapping);
        }
    }

    public function toArray(): array
    {
        return $this->report;
    }

    private function formatMapping(array $mapping)
    {
        $aggregation = [];

        foreach ($mapping as $commandName => $handler) {
            $aggregation[] = ['command' => $commandName, 'handler' => $handler];
        }

        return $aggregation;
    }
}
