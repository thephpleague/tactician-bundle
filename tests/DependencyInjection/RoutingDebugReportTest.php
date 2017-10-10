<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection;

use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilder;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilders;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;
use League\Tactician\Bundle\DependencyInjection\RoutingDebugReport;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use League\Tactician\Bundle\Tests\Fake\OtherFakeCommand;
use PHPUnit\Framework\TestCase;

class RoutingDebugReportTest extends TestCase
{
    public function testItShouldBuildValidReport()
    {
        // GIVEN
        $buses = [
            'default',
            'foo',
            'bar'
        ];

        $builders = new BusBuilders(
            [
                new BusBuilder($buses[0], 'some.method.inflector', ['middleware1', 'middleware2']),
                new BusBuilder($buses[1], 'some.method.inflector', ['middleware1', 'middleware2']),
                new BusBuilder($buses[2], 'some.method.inflector', ['middleware1', 'middleware2'])
            ],
            'default'
        );

        $routing = new Routing($buses);

        $routing->routeToAllBuses(FakeCommand::class, 'fake.handler');
        $routing->routeToBus('foo', OtherFakeCommand::class, 'other_fake.handler');

        // WHEN
        $report = new RoutingDebugReport($builders, $routing);

        // THEN
        $reportArray = $report->toArray();
        $this->assertCount(3, $reportArray);
        $this->assertArrayHasKey('default', $reportArray);
        $this->assertArrayHasKey('foo', $reportArray);
        $this->assertArrayHasKey('bar', $reportArray);

        $this->assertCount(1, $reportArray['default']);
        $this->assertCount(2, $reportArray['foo']);
        $this->assertCount(1, $reportArray['bar']);

        $mappings = array_merge($reportArray['default'], $reportArray['foo'], $reportArray['bar']);

        foreach ($mappings as $mapping) {
            $this->assertArrayHasKey('command', $mapping);
            $this->assertArrayHasKey('handler', $mapping);
        }
    }
}
