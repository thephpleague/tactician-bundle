<?php


namespace League\Tactician\Bundle\Tests\DependencyInjection;


use League\Tactician\Bundle\DependencyInjection\TacticianExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;

class TacticianExtensionTest extends AbstractExtensionTestCase
{
    /**
     * Return an array of container extensions you need to be registered for each test (usually just the container
     * extension you are testing.
     *
     * @return ExtensionInterface[]
     */
    protected function getContainerExtensions()
    {
        return [
            new TacticianExtension()
        ];
    }

    protected function getMinimalConfiguration()
    {
        return [
            'commandbus' => [
                'default' => [
                    'middleware' => [
                        'my_middleware.custom.stuff',
                        'tactician.middleware.command_handler',
                    ]
                ]
            ]
        ];
    }

    public function testCommandBusLoadsAndSetsDefault()
    {
        $this->load([
            'commandbus' => [
                'bus_1' => [
                    'middleware' => [
                        'my_middleware.custom.stuff',
                        'tactician.middleware.command_handler',
                    ]
                ],
                'bus_2' => [
                    'middleware' => [
                        'my_middleware.custom.stuff',
                        'tactician.middleware.command_handler',
                    ]
                ],
            ],
            'default_bus' => 'bus_2'
        ]);

        $this->assertContainerBuilderHasService('tactician.commandbus.bus_1');
        $this->assertContainerBuilderHasService('tactician.commandbus.bus_2');
        $this->assertContainerBuilderHasAlias('tactician.commandbus', 'tactician.commandbus.bus_2');
    }

    public function testCommandBusLoadsAndConfigures()
    {

        $middlewares = [
            new Reference('my_middleware.custom.stuff'),
            new Reference('tactician.middleware.command_handler')
        ];

        $this->load([
            'commandbus' => [
                'bus_1' => [
                    'middleware' => [
                        'my_middleware.custom.stuff',
                        'tactician.middleware.command_handler',
                    ]
                ],
                'bus_2' => [
                    'middleware' => [
                        'my_middleware.custom.stuff',
                        'tactician.middleware.command_handler',
                    ]
                ],
            ],
            'default_bus' => 'bus_2'
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('tactician.commandbus.bus_1', 0, $middlewares);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('tactician.commandbus.bus_2', 0, $middlewares);
    }

    public function testCommandBusLoadingDefault()
    {
        $this->load();
        $this->assertContainerBuilderHasService('tactician.commandbus.default');
    }

    public function testMethodNameInflectorDefault()
    {
        $this->load();
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'tactician.middleware.command_handler',
            2,
            new Reference('tactician.handler.method_name_inflector.handle')
        );
    }

    public function testMethodNameInflectorNonDefault()
    {
        $this->load([
            'method_inflector' => 'tactician.handler.method_name_inflector.handle_class_name_without_suffix'
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'tactician.middleware.command_handler',
            2,
            new Reference('tactician.handler.method_name_inflector.handle_class_name_without_suffix')
        );
    }
}
