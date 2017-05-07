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
        $this->assertContainerBuilderHasParameter('tactician.commandbus.ids', ['default', 'bus_1', 'bus_2']);
        $this->assertContainerBuilderHasParameter('tactician.commandbus.default', 'bus_2');
        $this->assertContainerBuilderHasParameter('tactician.method_inflector.default', 'tactician.handler.method_name_inflector.handle');
        $this->assertContainerBuilderHasParameter('tactician.method_inflector.bus_1', 'tactician.handler.method_name_inflector.handle');
        $this->assertContainerBuilderHasParameter('tactician.method_inflector.bus_2', 'tactician.handler.method_name_inflector.handle');
        $this->assertContainerBuilderHasAlias('tactician.commandbus', 'tactician.commandbus.bus_2');
    }

    public function testCommandBusLoadsAndSetsCorrectMethodInflector()
    {
        $this->load([
            'commandbus' => [
                'bus_1' => [
                    'middleware' => [
                        'my_middleware.custom.stuff',
                        'tactician.middleware.command_handler',
                    ],
                    'method_inflector' => 'my.inflector.service'
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

        $this->assertContainerBuilderHasParameter('tactician.method_inflector.bus_1', 'my.inflector.service');
        $this->assertContainerBuilderHasParameter('tactician.method_inflector.bus_2', 'tactician.handler.method_name_inflector.handle');
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

    public function testLoadSecurityConfiguration()
    {
        $securitySettings = ['Some\Command' => ['ROLE_USER'], 'Some\Other\Command' => ['ROLE_ADMIN']];

        $this->load([
            'commandbus' => [
                'default' => [
                    'middleware' => [
                        'tactician.middleware.security',
                        'tactician.middleware.command_handler',
                    ]
                ]
            ],
            'security' => $securitySettings
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('tactician.middleware.security_voter', 1, $securitySettings);
        $this->assertContainerBuilderHasServiceDefinitionWithTag('tactician.middleware.security_voter', 'security.voter');
    }

    public function testDefaultSecurityConfigurationIsAllowNothing()
    {
        $this->load([
            'commandbus' => [
                'default' => [
                    'middleware' => [
                        'tactician.middleware.security',
                        'tactician.middleware.command_handler',
                    ]
                ]
            ]
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('tactician.middleware.security_voter', 1, []);
        $this->assertContainerBuilderHasServiceDefinitionWithTag('tactician.middleware.security_voter', 'security.voter');
    }

    public function testVoterIsNotLoadedWithoutSecurityMiddleware()
    {
        $this->load();

        $this->assertContainerBuilderNotHasService('tactician.middleware.security_voter');
    }
}
