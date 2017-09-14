<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler;

use League\Tactician\Bundle\DependencyInjection\Compiler\CommandHandlerPass;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\ClassNameStrategy;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\MappingStrategy;
use League\Tactician\Bundle\Handler\ContainerBasedHandlerLocator;
use League\Tactician\CommandBus;
use League\Tactician\Container\ContainerLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ServiceLocator;

class CommandHandlerPassTest extends TestCase
{
    /**
     * @var MappingStrategy
     */
    private $mappingStrategy;

    protected function setUp()
    {
        $this->mappingStrategy = new ClassNameStrategy();
    }

    public function testProcess()
    {
        $container = $this->getContainer(
            ['commandbus' => ['default' => ['middleware' => []]]]
        );

        (new CommandHandlerPass($this->mappingStrategy))->process($container);

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $container,
            'default',
            'tactician.handler.method_name_inflector.handle'
        );
    }

// TODO: move to Routing tests
//    /**
//     * @expectedException        \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
//     * @expectedExceptionMessage The tactician.handler tag must always have a command attribute
//     */
//    public function testProcessAbortsOnMissingCommandAttribute()
//    {
//        $container = $this->getContainer(
//            ['commandbus' => ['default' => ['middleware' => []]]]
//        );
//        $container->register('handler_1')->addTag('tactician.handler', ['command' => 'my_command']);
//        $container->register('handler_2')->addTag('tactician.handler', ['not_command' => 'my_command']);
//
//        (new CommandHandlerPass($this->mappingStrategy))->process($container);
//    }

// TODO: move to routing tests
//    /**
//     * @expectedException        \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
//     * @expectedExceptionMessage Invalid bus id "bad_bus".
//     */
//    public function testProcessAbortsOnInvalidBus()
//    {
//        $container = $this->getContainer(
//            ['commandbus' => ['default' => ['middleware' => []]]]
//        );
//
//        $container
//            ->register('handler_1')
//            ->addTag('tactician.handler', ['command' => 'my_command', 'bus' => 'bad_bus']);
//
//        (new CommandHandlerPass($this->mappingStrategy))->process($container);
//    }

    public function testProcessAddsLocatorAndHandlerDefinitionForTaggedBuses()
    {
        $container = $this->getContainer(
            [
                'default_bus' => 'custom_bus',
                'commandbus' =>
                    [
                        'default' => ['middleware' => []],
                        'custom_bus' => ['middleware' => []],
                        'other_bus' => ['middleware' => []]
                    ]
            ]
        );
//        $container->setParameter(
//            'tactician.method_inflector.custom_bus',
//            'tactician.handler.method_name_inflector.handle'
//        );
//        $container->setParameter(
//            'tactician.method_inflector.other_bus',
//            'tactician.handler.method_name_inflector.handle'
//        );

//        $container
//            ->register('handler_1')
//            ->addTag('tactician.handler', ['command' => 'my_command', 'bus' => 'custom_bus'])
//            ->addTag('tactician.handler', ['command' => 'my_command', 'bus' => 'other_bus']);


        (new CommandHandlerPass($this->mappingStrategy))->process($container);

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $container,
            'default',
            'tactician.handler.method_name_inflector.handle',
            'custom_bus'
        );

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $container,
            'custom_bus',
            'tactician.handler.method_name_inflector.handle',
            'custom_bus'
        );

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $container,
            'other_bus',
            'tactician.handler.method_name_inflector.handle'
        );
    }

    public function testProcessAddsHandlerDefinitionWithNonDefaultMethodNameInflector()
    {
        $container = $this->getContainer(['default', 'custom_bus'], 'custom_bus');
        $container->setParameter(
            'tactician.method_inflector.custom_bus',
            'tactician.handler.method_name_inflector.handle_class_name'
        );

//        $container
//            ->register('handler_1')
//            ->addTag('tactician.handler', ['command' => 'my_command', 'bus' => 'custom_bus']);

        (new CommandHandlerPass($this->mappingStrategy))->process($container);

        $this->busShouldBeCorrectlyRegisteredInContainer(
            $container,
            'custom_bus',
            'tactician.handler.method_name_inflector.handle_class_name'
        );
    }

    private function busShouldBeCorrectlyRegisteredInContainer(
        ContainerBuilder $container,
        $busId,
        $methodInflector,
        $defaultBusId = 'default'
    ) {
        $handlerLocatorId = sprintf('tactician.commandbus.%s.handler.locator', $busId);
        $handlerId = sprintf('tactician.commandbus.%s.middleware.command_handler', $busId);

        if (class_exists(ServiceLocator::class)) {
            $this->assertSame(
                ServiceLocator::class,
                $container->getDefinition(
                    sprintf('tactician.commandbus.%s.handler.service_locator', $busId)
                )->getClass()
            );
        }

        $this->assertSame(
            class_exists(ServiceLocator::class)
                ? ContainerLocator::class
                : ContainerBasedHandlerLocator::class,
            $container->getDefinition($handlerLocatorId)->getClass()
        );

        $this->assertSame(
            $methodInflector,
            (string)$container
                ->getDefinition($handlerId)
                ->getArgument(2)
        );

        $this->assertSame(
            "tactician.commandbus.$defaultBusId.handler.locator",
            (string)$container->getAlias('tactician.handler.locator.symfony')
        );

        $this->assertSame(
            "tactician.commandbus.$defaultBusId.middleware.command_handler",
            (string)$container->getAlias('tactician.middleware.command_handler')
        );
    }

    private function getContainer($config)
    {
        $container = new ContainerBuilder();

        $container->setParameter('tactician.commandbus.class', CommandBus::class);
        $container->prependExtensionConfig('tactician', $config);
//        $container->setParameter(
//            'tactician.method_inflector.default',
//            'tactician.handler.method_name_inflector.handle'
//        );

//        foreach ($busIds as $busId) {
//            $container->register("tactician.commandbus.$busId")->addArgument(array());
//        }

        return $container;
    }
}
