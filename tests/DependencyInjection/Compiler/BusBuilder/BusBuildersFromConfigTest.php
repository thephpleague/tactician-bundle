<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler\BusBuilder;

use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilder;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuildersFromConfig;
use PHPUnit\Framework\TestCase;

final class BusBuildersFromConfigTest extends TestCase
{
    public function test_config_leads_to_builder_with_default_for_each_commandbus() : void
    {
        $builders = BusBuildersFromConfig::convert([
            'commandbus' => [
                'default' => [
                    'middleware' => [
                        'my.middleware',
                    ],
                ],
                'other' => [
                    'middleware' => [
                        'my.other.middleware',
                    ],
                ],
            ],
        ]);

        self::assertEquals(
            new BusBuilder('default', BusBuildersFromConfig::DEFAULT_COMMAND_HANDLER_MAPPING, ['my.middleware']),
            $builders->getIterator()['default']
        );
        self::assertEquals(
            new BusBuilder('other', BusBuildersFromConfig::DEFAULT_COMMAND_HANDLER_MAPPING, ['my.other.middleware']),
            $builders->getIterator()['other']
        );
    }

    public function test_default_command_handler_mapping_can_be_overrided() : void
    {
        $builders = BusBuildersFromConfig::convert([
            'command_handler_mapping' => 'other.mapping',
            'commandbus' => [
                'default' => [
                    'middleware' => [
                        'my.middleware',
                    ],
                ],
                'other' => [
                    'middleware' => [
                        'my.other.middleware',
                    ],
                ],
            ],
        ]);


        self::assertEquals(
            new BusBuilder('default', 'other.mapping', ['my.middleware']),
            $builders->getIterator()['default']
        );
    }

    public function test_command_handler_mapping_of_each_bus_can_be_overrided() : void
    {
        $builders = BusBuildersFromConfig::convert([
            'command_handler_mapping' => 'other.mapping',
            'commandbus' => [
                'default' => [
                    'middleware' => [
                        'my.middleware',
                    ],
                ],
                'other' => [
                    'command_handler_mapping' => 'bus2.mapping',
                    'middleware' => [
                        'my.other.middleware',
                    ],
                ],
            ],
        ]);

        self::assertEquals(
            new BusBuilder('other', 'bus2.mapping', ['my.other.middleware']),
            $builders->getIterator()['other']
        );
    }

    public function test_default_bus_is_set() : void
    {
        $builders = BusBuildersFromConfig::convert([
            'commandbus' => [
                'default' => [
                    'middleware' => [
                        'my.middleware',
                    ],
                ],
                'other' => [
                    'middleware' => [
                        'my.other.middleware',
                    ],
                ],
            ],
        ]);

        self::assertEquals(
            new BusBuilder('default', BusBuildersFromConfig::DEFAULT_COMMAND_HANDLER_MAPPING, ['my.middleware']),
            $builders->defaultBus()
        );
    }

    public function test_default_bus_can_be_overrided() : void
    {
        $builders = BusBuildersFromConfig::convert([
            'default_bus' => 'other',
            'commandbus' => [
                'default' => [
                    'middleware' => [
                        'my.middleware',
                    ],
                ],
                'other' => [
                    'middleware' => [
                        'my.other.middleware',
                    ],
                ],
            ],
        ]);

        self::assertEquals(
            new BusBuilder('other', BusBuildersFromConfig::DEFAULT_COMMAND_HANDLER_MAPPING, ['my.other.middleware']),
            $builders->defaultBus()
        );
    }
}
