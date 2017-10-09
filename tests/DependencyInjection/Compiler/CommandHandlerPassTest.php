<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler;

use League\Tactician\Bundle\DependencyInjection\Compiler\CommandHandlerPass;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\ClassNameMapping;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\HandlerMapping;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CommandHandlerPassTest extends TestCase
{
    /**
     * @var HandlerMapping
     */
    private $mappingStrategy;

    protected function setUp()
    {
        $this->mappingStrategy = new ClassNameMapping();
    }

    // TODO: Somehow assert that routing is run and mapped onto the locators

    public function testAddingSingleDefaultBus()
    {
        $container = $this->containerWithConfig(
            [
                'commandbus' =>
                    [
                        'default' => ['middleware' => []],
                    ]
            ]
        );

        (new CommandHandlerPass($this->mappingStrategy))->process($container);

        $this->assertTrue($container->hasDefinition('tactician.commandbus.default'));

        $this->assertDefaultAliasesAreDeclared($container, 'default');
    }

    public function testProcessAddsLocatorAndHandlerDefinitionForTaggedBuses()
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

        (new CommandHandlerPass($this->mappingStrategy))->process($container);

        $this->assertTrue($container->hasDefinition('tactician.commandbus.default'));
        $this->assertTrue($container->hasDefinition('tactician.commandbus.custom_bus'));
        $this->assertTrue($container->hasDefinition('tactician.commandbus.other_bus'));

        $this->assertDefaultAliasesAreDeclared($container, 'custom_bus');
    }

    private function containerWithConfig($config)
    {
        $container = new ContainerBuilder();

        $container->prependExtensionConfig('tactician', $config);

        return $container;
    }

    /**
     * @param $container
     */
    protected function assertDefaultAliasesAreDeclared(ContainerBuilder $container, string $defaultBusId)
    {
        $this->assertSame(
            $container->findDefinition('tactician.commandbus'),
            $container->getDefinition("tactician.commandbus.$defaultBusId")
        );

        $this->assertSame(
            $container->findDefinition('tactician.handler.locator.symfony'),
            $container->getDefinition("tactician.commandbus.$defaultBusId.handler.locator")
        );

        $this->assertSame(
            $container->findDefinition('tactician.middleware.command_handler'),
            $container->getDefinition("tactician.commandbus.$defaultBusId.middleware.command_handler")
        );
    }
}
