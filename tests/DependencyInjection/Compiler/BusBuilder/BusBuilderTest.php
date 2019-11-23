<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler\BusBuilder;

use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BusBuilderTest extends TestCase
{
    public function test_default_name_generates_expected_ids() : void
    {
        $builder = new BusBuilder('default', 'some.command_handler_mapping', ['middleware1', 'middleware2']);

        self::assertEquals('default', $builder->id());
        self::assertEquals('tactician.commandbus.default', $builder->serviceId());
        self::assertEquals('tactician.commandbus.default.middleware.command_handler', $builder->commandHandlerMiddlewareId());
    }

    public function test_alternate_name_generates_expected_ids() : void
    {
        $builder = new BusBuilder('foobar', 'some.command_handler_mapping', ['middleware1', 'middleware2']);

        self::assertEquals('foobar', $builder->id());
        self::assertEquals('tactician.commandbus.foobar', $builder->serviceId());
        self::assertEquals('tactician.commandbus.foobar.middleware.command_handler', $builder->commandHandlerMiddlewareId());
    }

    public function testProcess() : void
    {
        $builder = new BusBuilder('default', 'some.command_handler_mapping', ['middleware1', 'middleware2']);

        $builder->registerInContainer($container = new ContainerBuilder());

        $this->busShouldBeCorrectlyRegisteredInContainer($container, 'default', 'some.command_handler_mapping');
    }

    private function busShouldBeCorrectlyRegisteredInContainer(ContainerBuilder $container, string $busId, string $commandHandlerMapping) : void
    {
        $handlerId = "tactician.commandbus.$busId.middleware.command_handler";

        self::assertSame(
            $commandHandlerMapping,
            (string) $container
                ->getDefinition($handlerId)
                ->getArgument(1)
        );
    }
}
