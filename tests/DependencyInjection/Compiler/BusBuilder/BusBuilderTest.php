<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler\BusBuilder;

use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilder;
use League\Tactician\Bundle\Handler\ContainerBasedHandlerLocator;
use League\Tactician\Container\ContainerLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class BusBuilderTest extends TestCase
{
    public function test_default_name_generates_expected_ids()
    {
        $builder = new BusBuilder('default', 'some.method.inflector', ['middleware1', 'middleware2']);

        $this->assertEquals('default', $builder->id());
        $this->assertEquals('tactician.commandbus.default', $builder->serviceId());
        $this->assertEquals('tactician.commandbus.default.handler.locator', $builder->locatorServiceId());
        $this->assertEquals('tactician.commandbus.default.middleware.command_handler', $builder->commandHandlerMiddlewareId());
    }

    public function test_alternate_name_generates_expected_ids()
    {
        $builder = new BusBuilder('foobar', 'some.method.inflector', ['middleware1', 'middleware2']);

        $this->assertEquals('foobar', $builder->id());
        $this->assertEquals('tactician.commandbus.foobar', $builder->serviceId());
        $this->assertEquals('tactician.commandbus.foobar.handler.locator', $builder->locatorServiceId());
        $this->assertEquals('tactician.commandbus.foobar.middleware.command_handler', $builder->commandHandlerMiddlewareId());
    }

    public function testProcess()
    {
        $builder = new BusBuilder('default', 'some.class.inflector', 'some.method.inflector', ['middleware1', 'middleware2']);

        $builder->registerInContainer($container = new ContainerBuilder());

        $this->busShouldBeCorrectlyRegisteredInContainer($container, 'default', 'some.method.inflector');
    }

    private function busShouldBeCorrectlyRegisteredInContainer(ContainerBuilder $container, $busId, $methodInflector)
    {
        $handlerLocatorId = "tactician.commandbus.$busId.handler.locator";
        $handlerId = "tactician.commandbus.$busId.middleware.command_handler";

        if (class_exists(ServiceLocator::class)) {
            $this->assertSame(
                ServiceLocator::class,
                $container->getDefinition("tactician.commandbus.$busId.handler.service_locator")->getClass()
            );
        }

        $this->assertSame(
            class_exists(ServiceLocator::class) ? ContainerLocator::class : ContainerBasedHandlerLocator::class,
            $container->getDefinition($handlerLocatorId)->getClass()
        );

        $this->assertSame(
            $methodInflector,
            (string)$container
                ->getDefinition($handlerId)
                ->getArgument(2)
        );
    }
}
