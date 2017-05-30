<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler;

use League\Tactician\Bundle\DependencyInjection\Compiler\CommandHandlerPass;
use League\Tactician\Bundle\Handler\ContainerBasedHandlerLocator;
use League\Tactician\Container\ContainerLocator;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ServiceLocator;

class CommandHandlerPassTest extends TestCase
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
        $this->parametersShouldBe(
            $this->container,
            [
                'tactician.commandbus.default' => 'default',
                'tactician.method_inflector.default' => 'tactician.handler.method_name_inflector.handle',
                'tactician.commandbus.ids' => ['default'],
            ]
        );

        $this->servicesTaggedShouldBe(
            $this->container,
            [
                'service_id_1' => [
                    ['command' => 'my_command'],
                ],
                'service_id_2' => [
                    ['command' => 'my_command'],
                ],
            ]
        );

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $this->container,
            'default',
            'tactician.handler.method_name_inflector.handle'
        );

        $this->compiler->process($this->container);
    }

    /**
     * @expectedException \Exception
     */
    public function testProcessAbortsOnMissingCommandAttribute()
    {
        $this->parametersShouldBe(
            $this->container,
            [
                'tactician.commandbus.default' => 'default',
                'tactician.method_inflector.default' => 'tactician.handler.method_name_inflector.handle',
                'tactician.commandbus.ids' => ['default'],
            ]
        );

        $this->servicesTaggedShouldBe(
            $this->container,
            [
                'service_id_1' => [
                    ['not_command' => 'my_command'],
                ],
                'service_id_2' => [
                    ['command' => 'my_command'],
                ],
            ]);

        $this->compiler->process($this->container);
    }

    /**
     * @expectedException \Exception
     */
    public function testProcessAbortsOnInvalidBus()
    {
        $this->parametersShouldBe(
            $this->container,
            [
                'tactician.commandbus.default' => 'default',
                'tactician.method_inflector.default' => 'tactician.handler.method_name_inflector.handle',
                'tactician.commandbus.ids' => ['default'],
            ]
        );

        $this->servicesTaggedShouldBe(
            $this->container,
            [
                'service_id_1' => [
                    ['command' => 'my_command', 'bus' => 'bad_bus_name'],
                ],
            ]);

        $this->compiler->process($this->container);
    }

    public function testProcessAddsLocatorAndHandlerDefinitionForTaggedBuses()
    {
        $this->parametersShouldBe(
            $this->container,
            [
                'tactician.commandbus.default' => 'custom_bus',
                'tactician.method_inflector.default' => 'tactician.handler.method_name_inflector.handle',
                'tactician.method_inflector.custom_bus' => 'tactician.handler.method_name_inflector.handle',
                'tactician.method_inflector.other_bus' => 'tactician.handler.method_name_inflector.handle',
                'tactician.commandbus.ids' => ['default', 'custom_bus', 'other_bus'],
            ]
        );

        $this->servicesTaggedShouldBe(
            $this->container,
            [
                'service_id_1' => [
                    ['command' => 'my_command', 'bus' => 'custom_bus'],
                    ['command' => 'my_command', 'bus' => 'other_bus'],
                ],
            ]);

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $this->container,
            'default',
            'tactician.handler.method_name_inflector.handle'
        );

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $this->container,
            'custom_bus',
            'tactician.handler.method_name_inflector.handle'
        );

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $this->container,
            'other_bus',
            'tactician.handler.method_name_inflector.handle'
        );

        $this->compiler->process($this->container);
    }

    public function testProcessAddsHandlerDefinitionWithNonDefaultMethodNameInflector()
    {
        $this->parametersShouldBe(
            $this->container,
            [
                'tactician.commandbus.default' => 'custom_bus',
                'tactician.method_inflector.custom_bus' => 'tactician.handler.method_name_inflector.handle_class_name',
                'tactician.commandbus.ids' => ['custom_bus'],
            ]
        );

        $this->servicesTaggedShouldBe(
            $this->container,
            [
                'service_id_1' => [
                    ['command' => 'my_command', 'bus' => 'custom_bus'],
                ],
            ]);

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $this->container,
            'custom_bus',
            'tactician.handler.method_name_inflector.handle_class_name'
        );

        $this->compiler->process($this->container);
    }

    private function parametersShouldBe($container, array $parameters)
    {
        foreach ($parameters as $key => $value) {
            $container->shouldReceive('getParameter')
                ->with($key)
                ->once()
                ->andReturn($value)
            ;
        }
    }

    private function servicesTaggedShouldBe($container, array $config)
    {
        $container->shouldReceive('findTaggedServiceIds')
            ->with('tactician.handler')
            ->once()
            ->andReturn($config)
        ;
    }

    private function busShouldBeCorrectlyRegisteredInContainer($container, $busId, $methodInflector)
    {
        $handlerLocatorId = sprintf('tactician.commandbus.%s.handler.locator', $busId);
        $handlerId = sprintf('tactician.commandbus.%s.middleware.command_handler', $busId);

        if (class_exists(ServiceLocator::class)) {
            $container->shouldReceive('setDefinition')
                ->with(
                    sprintf('tactician.commandbus.%s.handler.service_locator', $busId),
                    \Mockery::on(function (Definition $definition) {
                        $this->assertSame(ServiceLocator::class, $definition->getClass());

                        return true;
                    })
                )
            ;
        }

        $container->shouldReceive('setDefinition')
            ->with(
                $handlerLocatorId,
                \Mockery::on(function (Definition $definition) {
                    $this->assertSame(class_exists(ServiceLocator::class) ? ContainerLocator::class : ContainerBasedHandlerLocator::class, $definition->getClass());

                    return true;
                })
            )
            ->once()
        ;

        $this->container->shouldReceive('setDefinition')
            ->with(
                $handlerId,
                \Mockery::on(function (Definition $definition) use ($methodInflector) {
                    $methodNameInflectorServiceId = (string) $definition->getArgument(2);

                    return $methodNameInflectorServiceId === $methodInflector;
                })
            )
            ->once()
        ;

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

        $definition = \Mockery::mock(Definition::class);
        $definition->shouldReceive('getArgument')->andReturn([]);
        $this->container->shouldReceive('getDefinition')
            ->with('tactician.commandbus.'.$busId)
            ->once()
            ->andReturn($definition)
        ;
    }
}
