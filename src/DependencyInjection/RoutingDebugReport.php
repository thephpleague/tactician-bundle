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

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function toArray(): array
    {
        return $this->report;
    }
}
