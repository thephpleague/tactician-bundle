<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection;

use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilders;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;

class RoutingDebugReport
{
    /**
     * @var array
     */
    private $report = [];

    private function __construct(array $report)
    {
        $this->report = $report;
    }

    public static function fromBuildInfo(BusBuilders $builders, Routing $routing)
    {
        $report = [];

        foreach ($builders as $builder) {
            $report[$builder->id()] = $routing->commandToServiceMapping($builder->id());
        }

        return new static($report);
    }

    public static function fromArray(array $report)
    {
        return new static($report);
    }

    public function toArray(): array
    {
        return $this->report;
    }
}
