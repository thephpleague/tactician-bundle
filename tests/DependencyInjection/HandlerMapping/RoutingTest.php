<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection\HandlerMapping;

use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;
use League\Tactician\Bundle\DependencyInjection\InvalidCommandBusId;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use League\Tactician\Bundle\Tests\Fake\OtherFakeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class RoutingTest extends TestCase
{
    public function test_routing_command_to_specific_bus()
    {
        $routing = new Routing(['bus1', 'bus2']);
        $routing->routeToBus('bus1', FakeCommand::class, 'some.handler.1');
        $routing->routeToBus('bus2', OtherFakeCommand::class, 'some.handler.2');

        $this->assertEquals([FakeCommand::class => 'some.handler.1'], $routing->commandToServiceMapping('bus1'));
        $this->assertEquals([OtherFakeCommand::class => 'some.handler.2'], $routing->commandToServiceMapping('bus2'));
    }

    public function test_routing_to_all_buses()
    {
        $routing = new Routing(['bus1', 'bus2']);
        $routing->routeToAllBuses(FakeCommand::class, 'some.handler');

        $this->assertEquals([FakeCommand::class => 'some.handler'], $routing->commandToServiceMapping('bus1'));
        $this->assertEquals([FakeCommand::class => 'some.handler'], $routing->commandToServiceMapping('bus2'));
    }

    public function test_mixture_of_broadcast_and_specific_routing_commands()
    {
        $routing = new Routing(['bus1', 'bus2']);
        $routing->routeToAllBuses(FakeCommand::class, 'very.broad.handler');
        $routing->routeToBus('bus1', OtherFakeCommand::class, 'some.specific.handler');

        $this->assertEquals(
            [FakeCommand::class => 'very.broad.handler', OtherFakeCommand::class => 'some.specific.handler'],
            $routing->commandToServiceMapping('bus1')
        );
        $this->assertEquals([FakeCommand::class => 'very.broad.handler'], $routing->commandToServiceMapping('bus2'));
    }

    public function test_can_not_get_mapping_for_unknown_bus()
    {
        $routing = new Routing(['default']);

        $this->expectExceptionObject(InvalidCommandBusId::ofName('fake_bus', ['default']));
        $routing->commandToServiceMapping('fake_bus');
    }

    public function test_will_not_route_unknown_class_name()
    {
        $routing = new Routing(['default']);

        $this->expectExceptionObject(
            new InvalidArgumentException('Can not route Legit\Class to some.handler.service, class Legit\Class does not exist!')
        );
        $routing->routeToBus('default', 'Legit\Class', 'some.handler.service');
    }

    public function test_will_not_accept_command_on_invalid_bus_id()
    {
        $routing = new Routing(['bus1', 'bus2']);

        $this->expectExceptionObject(InvalidCommandBusId::ofName('bus3', ['bus1', 'bus2']));
        $routing->routeToBus('bus3', FakeCommand::class, 'some.handler.service');
    }
}
