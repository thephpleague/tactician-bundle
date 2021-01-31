<?php


namespace League\Tactician\Bundle\Tests\DependencyInjection;

use League\Tactician\Bundle\DependencyInjection\Configuration;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

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

    public function testDefaultConfiguration()
    {
        $this->assertProcessedConfigurationEquals(
            [],
            [
                'commandbus' => ['default' => ['middleware' => ['tactician.middleware.command_handler']]],
                'default_bus' => 'default',
                'method_inflector' => 'tactician.handler.method_name_inflector.handle',
                'security' => [],
                'logger_formatter' => 'tactician.logger.class_properties_formatter'
            ]
        );
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
            //we use a regexp to support the slightly different message thrown by symfony >=5.0
            '#Invalid type for path "tactician.commandbus.default.middleware.my_middleware"\. Expected "?scalar"?, but got "?array"?\.#',
            true
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
            'The default_bus "foo" was not defined as a command bus.'
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
            'The default_bus "default" was not defined as a command bus.'
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
            '"tactician.middleware.command_handler" should be the last middleware loaded when it is used.'
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

    public function testSecurityConfiguration()
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
                ],
                'security' => [
                    'Some\Command' => ['ROLE_USER'],
                    'Some\Other\Command' => ['ROLE_ADMIN'],
                ]
            ]
        ]);
    }

    public function testCustomLoggerFormatterCanBeSet()
    {
        $this->assertConfigurationIsValid(
            [
                'tactician' => [
                    'logger_formatter' => 'some.formatter.service',
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
