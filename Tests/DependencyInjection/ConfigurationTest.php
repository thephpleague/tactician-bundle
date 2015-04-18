<?php


namespace League\Tactician\Bundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\AbstractConfigurationTestCase;
use League\Tactician\Bundle\DependencyInjection\Configuration;

class ConfigurationTest extends AbstractConfigurationTestCase
{
    /**
     * Return the instance of ConfigurationInterface that should be used by the
     * Configuration-specific assertions in this test-case
     *
     * @return \Symfony\Component\Config\Definition\ConfigurationInterface
     */
    protected function getConfiguration()
    {
        return new Configuration();
    }

    public function testBlankConfiguration()
    {
        $this->assertConfigurationIsValid([]);
    }

    public function testSimpleMiddleware()
    {
        $this->assertConfigurationIsValid([
            'tactician' => [
                'commandbus' => [
                    'default' => [
                        'middleware' => [
                            'my_middleware'  => 'some_middleware',
                            'my_middleware2' => 'some_middleware',
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function testMiddlewareMustBeScalar()
    {
        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                                'my_middleware'  => [],
                                'my_middleware2' => 'some_middleware',
                            ]
                        ]
                    ]
                ]
            ],
            'Invalid type for path "tactician.commandbus.default.middleware.my_middleware". Expected scalar, but got array.'
        );
    }

    public function testDefaultMiddlewareMustExist()
    {
        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'default_bus' => 'foo',
                    'commandbus' => [
                        'bar' => [
                            'middleware' => [
                                'my_middleware'  => 'some_middleware',
                            ]
                        ]
                    ]
                ]
            ],
            'The default_bus "foo" was not defined as command bus.'
        );

        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'commandbus' => [
                        'bar' => [
                            'middleware' => [
                                'my_middleware'  => 'some_middleware',
                            ]
                        ]
                    ]
                ]
            ],
            'The default_bus "default" was not defined as command bus.'
        );
    }

    public function testMiddlewareDefinitionCannotBeEmpty()
    {
        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                            ]
                        ]
                    ]
                ]
            ],
            'The path "tactician.commandbus.default.middleware" should have at least 1 element(s) defined.'
        );

        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'commandbus' => [
                        'foo' => [
                            'middleware' => [
                            ]
                        ]
                    ]
                ]
            ],
            'The path "tactician.commandbus.foo.middleware" should have at least 1 element(s) defined.'
        );
    }

    public function testCommandHandlerMiddlewareIfPresentAndNotLastIsInvalid()
    {
        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                                'tactician.middleware.command_handler',
                                'my_middleware.custom.stuff',

                            ]
                        ]
                    ]
                ]
            ],
            '"tactician.middleware.command_handler" should be last loaded middleware when it is use.'
        );
    }

    public function testCommandHandlerMiddlewarePresentAndLastIsValid()
    {
        $this->assertConfigurationIsValid(
            [
                'tactician' => [
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                                'my_middleware.custom.stuff',
                                'tactician.middleware.command_handler',
                            ]
                        ]
                    ]
                ]
            ]
        );
    }
    public function testCommandHandlerMiddlewareNotPresentDoesNotAffectValidation()
    {
        $this->assertConfigurationIsValid(
            [
                'tactician' => [
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                                'my_middleware.custom.stuff',
                                'my_middleware.custom.other_stuff',
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function testCustomMethodInflectorCanBeSet()
    {
        $this->assertConfigurationIsValid(
            [
                'tactician' => [
                    'method_inflector' => 'some.inflector.service',
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                                'my_middleware.custom.stuff',
                                'my_middleware.custom.other_stuff',
                            ],
                        ],
                        'second' => [
                            'middleware' => [
                                'my_middleware.custom.stuff',
                                'my_middleware.custom.other_stuff',
                            ]
                        ]
                    ]
                ]
            ]
        );
    }
}
