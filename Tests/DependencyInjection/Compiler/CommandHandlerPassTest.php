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
                'commandbus' => [
                    'default' => []
                ]
            ]);

        $this->container->shouldReceive('has')
            ->with('tactician.handler.locator.symfony')
            ->once()
            ->andReturn(true);

        $this->container->shouldReceive('findDefinition')
            ->with('tactician.handler.locator.symfony')
            ->once()
            ->andReturn($definition);

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

        $definition->shouldReceive('addArgument')
            ->once();

        $this->compiler->process($this->container);
    }

    /**
     * @expectedException \Exception
     */
    public function testProcessAbortsOnMissingLocator()
    {
        $definition = \Mockery::mock(Definition::class);

        $this->container->shouldReceive('getExtensionConfig')
            ->with('tactician')
            ->once();

        $this->container->shouldReceive('has')
            ->with('tactician.handler.locator.symfony')
            ->once()
            ->andReturn(false);

        $this->container->shouldReceive('findDefinition')
            ->never();

        $this->container->shouldReceive('findTaggedServiceIds')
            ->never();

        $definition->shouldReceive('addArgument')
            ->never();

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
            ->once();

        $this->container->shouldReceive('has')
            ->with('tactician.handler.locator.symfony')
            ->once()
            ->andReturn(true);

        $this->container->shouldReceive('findDefinition')
            ->with('tactician.handler.locator.symfony')
            ->once()
            ->andReturn($definition);

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

        $definition->shouldReceive('addArgument')
            ->never();

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

        $this->container->shouldReceive('has')
            ->with('tactician.handler.locator.symfony')
            ->once()
            ->andReturn(true);

        $this->container->shouldReceive('findDefinition')
            ->with('tactician.handler.locator.symfony')
            ->once()
            ->andReturn($definition);

        $this->container->shouldReceive('findTaggedServiceIds')
            ->with('tactician.handler')
            ->once()
            ->andReturn([
                'service_id_1' => [
                    ['command' => 'my_command', 'bus' => 'bad_bus_name']
                ],
            ]);

        $definition->shouldReceive('addArgument')
            ->never();

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
                'commandbus' => [
                    'custom_bus' => []
                ]
            ]);

        $this->container->shouldReceive('has')
            ->with('tactician.handler.locator.symfony')
            ->once()
            ->andReturn(true);

        $this->container->shouldReceive('findDefinition')
            ->with('tactician.handler.locator.symfony')
            ->once()
            ->andReturn($definition);

        $this->container->shouldReceive('findTaggedServiceIds')
            ->with('tactician.handler')
            ->once()
            ->andReturn([
                'service_id_1' => [
                    ['command' => 'my_command', 'bus' => 'custom_bus']
                ],
            ]);

        $this->container->shouldReceive('setDefinition')
            ->with(
                'tactician.commandbus.custom_bus.handler.locator',
                \Mockery::type('Symfony\Component\DependencyInjection\Definition')
            );

        $this->container->shouldReceive('setDefinition')
            ->with(
                'tactician.commandbus.custom_bus.middleware.command_handler',
                \Mockery::type('Symfony\Component\DependencyInjection\Definition')
            );

        $this->container->shouldReceive('setAlias')
            ->with(
                'tactician.handler.locator.symfony',
                'tactician.commandbus.custom_bus.handler.locator'
            )
            ->once();

        $definition->shouldReceive('addArgument')
            ->once();

        $this->compiler->process($this->container);
    }
}
