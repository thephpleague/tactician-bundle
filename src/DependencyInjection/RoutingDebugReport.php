<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection;

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
