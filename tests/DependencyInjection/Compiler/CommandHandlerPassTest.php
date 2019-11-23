<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler;

use League\Tactician\Bundle\Console\DebugCommand;
use League\Tactician\Bundle\DependencyInjection\Compiler\CommandHandlerPass;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\ClassNameMapping;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\HandlerMapping;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use League\Tactician\Bundle\Tests\Fake\OtherFakeCommand;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CommandHandlerPassTest extends TestCase
{
//    /**
//     * @var HandlerMapping
//     */
//    private $mappingStrategy;
//
//    protected function setUp() : void
//    {
//        $this->mappingStrategy = new ClassNameMapping();
//    }

    public function testAddingSingleDefaultBus() : void
    {
        $container = $this->containerWithConfig(
            [
                'commandbus' =>
                    [
                        'default' => ['middleware' => []],
                    ]
            ]
        );

        (new CommandHandlerPass())->process($container);

        self::assertTrue($container->hasDefinition('tactician.commandbus.default'));

        $this->assertDefaultAliasesAreDeclared($container, 'default');
    }

    public function testProcessAddsHandlerDefinitionForTaggedBuses() : void
    {
        $container = $this->containerWithConfig(
            [
                'default_bus' => 'custom_bus',
                'commandbus' =>
                    [
                        'default' => ['middleware' => ['one']],
                        'custom_bus' => ['middleware' => ['two']],
                        'other_bus' => ['middleware' => ['three']]
                    ]
            ]
        );

        (new CommandHandlerPass())->process($container);

        self::assertTrue($container->hasDefinition('tactician.commandbus.default'));
        self::assertTrue($container->hasDefinition('tactician.commandbus.custom_bus'));
        self::assertTrue($container->hasDefinition('tactician.commandbus.other_bus'));

        $this->assertDefaultAliasesAreDeclared($container, 'custom_bus');
    }

    public function test_handler_mapping_is_called() : void
    {
        $container = $this->containerWithConfig(
            [
                'commandbus' => [ 'default' => ['middleware' => []] ]
            ]
        );

        $routing = new Routing(['default']);
        $routing->routeToAllBuses(FakeCommand::class, 'some.handler');

        $mapping = $this->prophesize(HandlerMapping::class);
        $mapping->build($container, Argument::type(Routing::class))->willReturn($routing);

        (new CommandHandlerPass($mapping->reveal()))->process($container);

        self::assertEquals(
            [FakeCommand::class => 'some.handler'],
            $container->getDefinition('tactician.commandbus.default.handler.locator')->getArgument(1)
        );
    }

    public function test_handler_mapping_is_kept_bus_specific()
    {
        $container = $this->containerWithConfig(
            [
                'default_bus' => 'bus.a',
                'commandbus' => [
                    'bus.a' => ['middleware' => []],
                    'bus.b' => ['middleware' => []]
                ]
            ]
        );

        $routing = new Routing(['bus.a', 'bus.b']);
        $routing->routeToBus('bus.a', FakeCommand::class, 'some.handler.a');
        $routing->routeToBus('bus.b', FakeCommand::class, 'some.handler.b');
        $routing->routeToAllBuses(OtherFakeCommand::class, 'some.other.handler');

        $mapping = $this->prophesize(HandlerMapping::class);
        $mapping->build($container, Argument::type(Routing::class))->willReturn($routing);

        (new CommandHandlerPass($mapping->reveal()))->process($container);

        self::assertEquals(
            [FakeCommand::class => 'some.handler.a', OtherFakeCommand::class => 'some.other.handler'],
            $container->getDefinition('tactician.commandbus.bus.a.handler.locator')->getArgument(1)
        );
        self::assertEquals(
            [FakeCommand::class => 'some.handler.b', OtherFakeCommand::class => 'some.other.handler'],
            $container->getDefinition('tactician.commandbus.bus.b.handler.locator')->getArgument(1)
        );
    }

    public function test_handler_wires_debug_command()
    {
        $container = $this->containerWithConfig(
            [
                'default_bus' => 'bus.a',
                'commandbus' => [
                    'bus.a' => ['middleware' => []],
                    'bus.b' => ['middleware' => []]
                ]
            ]
        );

        $routing = new Routing(['bus.a', 'bus.b']);
        $routing->routeToBus('bus.a', FakeCommand::class, 'some.handler.a');
        $routing->routeToBus('bus.b', FakeCommand::class, 'some.handler.b');
        $routing->routeToAllBuses(OtherFakeCommand::class, 'some.other.handler');

        $mapping = $this->prophesize(HandlerMapping::class);
        $mapping->build($container, Argument::type(Routing::class))->willReturn($routing);

        $container->register('tactician.command.debug', DebugCommand::class);

        (new CommandHandlerPass($mapping->reveal()))->process($container);

        self::assertSame(
            [
                [
                   'bus.a' => $routing->commandToServiceMapping('bus.a'),
                   'bus.b' => $routing->commandToServiceMapping('bus.b'),
                ],
            ],
            $container->getDefinition('tactician.command.debug')->getArguments()
        );
    }

    private function containerWithConfig($config) : ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->setParameter('tactician.merged_config', $config);

        return $container;
    }

    protected function assertDefaultAliasesAreDeclared(ContainerBuilder $container, string $defaultBusId) : void
    {
        self::assertSame(
            $container->findDefinition('tactician.commandbus'),
            $container->getDefinition("tactician.commandbus.$defaultBusId")
        );

        self::assertSame(
            $container->findDefinition('tactician.middleware.command_handler'),
            $container->getDefinition("tactician.commandbus.$defaultBusId.middleware.command_handler")
        );
    }
}
