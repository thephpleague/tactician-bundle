<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection;

use League\Tactician\Bundle\DependencyInjection\RoutingDebugReport;
use League\Tactician\Bundle\Tests\DependencyInjection\HandlerMapping\TestMappingData;
use PHPUnit\Framework\TestCase;

class RoutingDebugReportTest extends TestCase
{
    public function testItShouldBuildValidReport()
    {
        // GIVEN
        $mappings = TestMappingData::example();

        // WHEN
        $report = new RoutingDebugReport($mappings);

        // THEN
        $reportArray = $report->toArray();

        $this->assertEquals(
            TestMappingData::example(),
            $reportArray
        );
    }
}
