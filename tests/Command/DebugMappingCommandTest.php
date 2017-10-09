<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\Command;

use League\Tactician\Bundle\Command\DebugMappingCommand;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilder;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilders;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;
use League\Tactician\Bundle\DependencyInjection\RoutingDebugReport;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use League\Tactician\Bundle\Tests\Fake\OtherFakeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DebugMappingCommandTest extends TestCase
{
    public function testItShouldExecuteSuccessfully()
    {
        // GIVEN
        $buses = [
            'default',
            'foo',
            'bar'
        ];

        $report = $this->prepareReport($buses);
        $command = new DebugMappingCommand($report);
        $tester = new CommandTester($command);

        // WHEN
        $tester->execute([]);
        $output = $tester->getDisplay();

        // THEN
        foreach ($buses as $bus) {
            self::assertContains("Bus: $bus", $output);
        }
    }

    /**
     * @param $buses
     * @return RoutingDebugReport
     */
    private function prepareReport($buses): RoutingDebugReport
    {
        $builders = new BusBuilders(
            array_map(function (string $bus) {
                return new BusBuilder($bus, 'some.method.inflector', ['middleware1', 'middleware2']);
            }, $buses),
            'default'
        );

        $routing = new Routing($buses);

        $routing->routeToBus('bar', FakeCommand::class, 'fake.handler');
        $routing->routeToBus('foo', OtherFakeCommand::class, 'other_fake.handler');

        return new RoutingDebugReport($builders, $routing);
    }
}
