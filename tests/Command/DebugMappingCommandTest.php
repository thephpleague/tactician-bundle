<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\Command;

use League\Tactician\Bundle\Command\DebugMappingCommand;
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
    private function prepareReport(array $buses): RoutingDebugReport
    {
        $routing = new Routing($buses);

        $routing->routeToBus('bar', FakeCommand::class, 'fake.handler');
        $routing->routeToBus('foo', OtherFakeCommand::class, 'other_fake.handler');

        $mappings = [];
        foreach ($buses as $bus) {
            $mappings[$bus] = $routing->commandToServiceMapping($bus);
        }

        return new RoutingDebugReport($mappings);
    }
}
