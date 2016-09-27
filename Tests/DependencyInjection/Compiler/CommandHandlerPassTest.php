<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler;

use Mockery\MockInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use League\Tactician\Bundle\DependencyInjection\Compiler\CommandHandlerPass;

class CommandHandlerPassTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ContainerBuilder | MockInterface
     */
    protected $container;

    /**
     * @var CommandHandlerPass
     */
    protected $compiler;

    protected function setUp()
    {
        parent::setUp();
        $this->container = \Mockery::mock(ContainerBuilder::class);

        $this->compiler = new CommandHandlerPass();
    }

    public function testProcess()
    {
        $definition = \Mockery::mock(Definition::class);

        $this->container->shouldReceive('getExtensionConfig')
            ->with('tactician')
            ->andReturn([
                'default_bus' => 'default',
                'method_inflector' => 'tactician.handler.method_name_inflector.handle',
                'commandbus' => [
                    'default' => []
                ]
            ]);

        $this->container->shouldReceive('findTaggedServiceIds')
            ->with('tactician.handler')
            ->once()
            ->andReturn([
                'service_id_1' => [
                    ['command' => 'my_command']
                ],
                'service_id_2' => [
                    ['command' => 'my_command']
                ],
            ]);

        $this->container->shouldReceive('setDefinition')
            ->twice();

        $this->container->shouldReceive('setAlias')
            ->with(
                'tactician.handler.locator.symfony',
                'tactician.commandbus.default.handler.locator'
            )
            ->once();

        $this->container->shouldReceive('setAlias')
            ->with(
                'tactician.middleware.command_handler',
                'tactician.commandbus.default.middleware.command_handler'
            )
            ->once();

        $this->compiler->process($this->container);
    }

    /**
     * @expectedException \Exception
     */
    public function testProcessAbortsOnMissingCommandAttribute()
    {
        $definition = \Mockery::mock(Definition::class);

        $this->container->shouldReceive('getExtensionConfig')
            ->with('tactician')
            ->twice()
            ->andReturn([
                'default_bus' => 'default',
                'commandbus' => []
            ]);

        $this->container->shouldReceive('findTaggedServiceIds')
            ->with('tactician.handler')
            ->once()
            ->andReturn([
                'service_id_1' => [
                    ['not_command' => 'my_command']
                ],
                'service_id_2' => [
                    ['command' => 'my_command']
                ],
            ]);

        $this->compiler->process($this->container);
    }

    /**
     * @expectedException \Exception
     */
    public function testProcessAbortsOnInvalidBus()
    {
        $definition = \Mockery::mock(Definition::class);

        $this->container->shouldReceive('getExtensionConfig')
            ->with('tactician')
            ->once()
            ->andReturn([
                'default_bus' => 'default',
                'commandbus' => [
                    'default' => []
                ]
            ]);

        $this->container->shouldReceive('findTaggedServiceIds')
            ->with('tactician.handler')
            ->once()
            ->andReturn([
                'service_id_1' => [
                    ['command' => 'my_command', 'bus' => 'bad_bus_name']
                ],
            ]);

        $this->compiler->process($this->container);
    }

    public function testProcessAddsLocatorAndHandlerDefinitionForTaggedBuses()
    {
        $definition = \Mockery::mock(Definition::class);

        $this->container->shouldReceive('getExtensionConfig')
            ->with('tactician')
            ->once()
            ->andReturn([
                'default_bus' => 'custom_bus',
                'method_inflector' => 'tactician.handler.method_name_inflector.handle',
                'commandbus' => [
                    'custom_bus' => [],
                    'other_bus' => [],
                ]
            ]);

        $this->container->shouldReceive('findTaggedServiceIds')
            ->with('tactician.handler')
            ->once()
            ->andReturn([
                'service_id_1' => [
                    ['command' => 'my_command', 'bus' => 'custom_bus']
                ]
            ]);

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $this->container,
            'custom_bus',
            'tactician.handler.method_name_inflector.handle'
        );

        $this->compiler->process($this->container);
    }

    public function testProcessAddsHandlerDefinitionWithNonDefaultMethodNameInflector()
    {
        $definition = \Mockery::mock(Definition::class);

        $this->container->shouldReceive('getExtensionConfig')
            ->with('tactician')
            ->once()
            ->andReturn([
                'default_bus' => 'custom_bus',
                'method_inflector' => 'tactician.handler.method_name_inflector.handle_class_name',
                'commandbus' => [
                    'custom_bus' => []
                ]
            ]);

        $this->container->shouldReceive('findTaggedServiceIds')
            ->with('tactician.handler')
            ->once()
            ->andReturn([
                'service_id_1' => [
                    ['command' => 'my_command', 'bus' => 'custom_bus']
                ],
            ]);

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $this->container,
            'custom_bus',
            'tactician.handler.method_name_inflector.handle_class_name'
        );

        $this->compiler->process($this->container);
    }

    private function busShouldBeCorrectlyRegisteredInContainer($container, $busId, $methodInflector)
    {
        $handlerLocatorId = sprintf('tactician.commandbus.%s.handler.locator', $busId);
        $handlerId = sprintf('tactician.commandbus.%s.middleware.command_handler', $busId);

        $container->shouldReceive('setDefinition')
            ->with(
                $handlerLocatorId,
                \Mockery::type('Symfony\Component\DependencyInjection\Definition')
            );

        $this->container->shouldReceive('setDefinition')
            ->with(
                $handlerId,
                \Mockery::on(function (Definition $definition) use ($methodInflector) {
                    $methodNameInflectorServiceId = (string) $definition->getArgument(2);

                    return $methodNameInflectorServiceId === $methodInflector;
                })
            );

        $this->container->shouldReceive('setAlias')
            ->with(
                'tactician.handler.locator.symfony',
                $handlerLocatorId
            )
            ->once();

        $this->container->shouldReceive('setAlias')
            ->with(
                'tactician.middleware.command_handler',
                $handlerId
            )
            ->once();
    }
}
