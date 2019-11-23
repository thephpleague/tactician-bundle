<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection;

use League\Tactician\Bundle\DependencyInjection\Configuration;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    /** {@inheritDoc} */
    protected function getConfiguration() : ConfigurationInterface
    {
        return new Configuration();
    }

    public function testBlankConfiguration() : void
    {
        $this->assertConfigurationIsValid([]);
    }

    public function testDefaultConfiguration() : void
    {
        $this->assertProcessedConfigurationEquals(
            [],
            [
                'commandbus'              => ['default' => ['middleware' => ['tactician.middleware.command_handler']]],
                'default_bus'             => 'default',
                'command_handler_mapping' => 'tactician.handler.command_handler_mapping.map_by_naming_convention',
            ]
        );
    }

    public function testSimpleMiddleware() : void
    {
        $this->assertConfigurationIsValid(
            [
                'tactician' => [
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                                'my_middleware'  => 'some_middleware',
                                'my_middleware2' => 'some_middleware',
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    public function testMiddlewareMustBeScalar() : void
    {
        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                                'my_middleware'  => [],
                                'my_middleware2' => 'some_middleware',
                            ],
                        ],
                    ],
                ],
            ],
            'Invalid type for path "tactician.commandbus.default.middleware.my_middleware". Expected scalar, but got array.'
        );
    }

    public function testDefaultMiddlewareMustExist() : void
    {
        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'default_bus' => 'foo',
                    'commandbus'  => [
                        'bar' => [
                            'middleware' => [
                                'my_middleware' => 'some_middleware',
                            ],
                        ],
                    ],
                ],
            ],
            'The default_bus "foo" was not defined as a command bus.'
        );

        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'commandbus' => [
                        'bar' => [
                            'middleware' => [
                                'my_middleware' => 'some_middleware',
                            ],
                        ],
                    ],
                ],
            ],
            'The default_bus "default" was not defined as a command bus.'
        );
    }

    public function testMiddlewareDefinitionCannotBeEmpty() : void
    {
        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                            ],
                        ],
                    ],
                ],
            ],
            'The path "tactician.commandbus.default.middleware" should have at least 1 element(s) defined.'
        );

        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'commandbus' => [
                        'foo' => [
                            'middleware' => [
                            ],
                        ],
                    ],
                ],
            ],
            'The path "tactician.commandbus.foo.middleware" should have at least 1 element(s) defined.'
        );
    }

    public function testCommandHandlerMiddlewareIfPresentAndNotLastIsInvalid() : void
    {
        $this->assertConfigurationIsInvalid(
            [
                'tactician' => [
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                                'tactician.middleware.command_handler',
                                'my_middleware.custom.stuff',

                            ],
                        ],
                    ],
                ],
            ],
            '"tactician.middleware.command_handler" should be the last middleware loaded when it is used.'
        );
    }

    public function testCommandHandlerMiddlewarePresentAndLastIsValid() : void
    {
        $this->assertConfigurationIsValid(
            [
                'tactician' => [
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                                'my_middleware.custom.stuff',
                                'tactician.middleware.command_handler',
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    public function testCommandHandlerMiddlewareNotPresentDoesNotAffectValidation() : void
    {
        $this->assertConfigurationIsValid(
            [
                'tactician' => [
                    'commandbus' => [
                        'default' => [
                            'middleware' => [
                                'my_middleware.custom.stuff',
                                'my_middleware.custom.other_stuff',
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    public function testCustomCommandHandlerMappingCanBeSet() : void
    {
        $this->assertConfigurationIsValid(
            [
                'tactician' => [
                    'command_handler_mapping' => 'some.command_handler_mapping.service',
                    'commandbus'       => [
                        'default' => [
                            'middleware' => [
                                'my_middleware.custom.stuff',
                                'my_middleware.custom.other_stuff',
                            ],
                        ],
                        'second'  => [
                            'middleware' => [
                                'my_middleware.custom.stuff',
                                'my_middleware.custom.other_stuff',
                            ],
                        ],
                    ],
                ],
            ]
        );
    }
}
