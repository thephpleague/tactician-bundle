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
}
