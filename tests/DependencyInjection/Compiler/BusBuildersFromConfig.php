<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler\BusBuilder;

use PHPUnit\Framework\TestCase;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilder;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuildersFromConfig;

final class BusBuildersFromConfigTest extends TestCase
{
    public function test_config_leads_to_builder_with_default_for_each_commandbus()
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

        $this->assertEquals(
            new BusBuilder('default', BusBuildersFromConfig::DEFAULT_METHOD_INFLECTOR, ['my.middleware']),
            $builders->getIterator()['default']
        );
        $this->assertEquals(
            new BusBuilder('other', BusBuildersFromConfig::DEFAULT_METHOD_INFLECTOR, ['my.other.middleware']),
            $builders->getIterator()['other']
        );
    }

    public function test_default_method_inflector_can_be_overrided()
    {
        $builders = BusBuildersFromConfig::convert([
            'method_inflector' => 'other.inflector',
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

        $this->assertEquals(
            new BusBuilder('default', 'other.inflector', ['my.middleware']),
            $builders->getIterator()['default']
        );
    }

    public function test_method_inflector_of_each_bus_can_be_overrided()
    {
        $builders = BusBuildersFromConfig::convert([
            'method_inflector' => 'other.inflector',
            'commandbus' => [
                'default' => [
                    'middleware' => [
                        'my.middleware',
                    ],
                ],
                'other' => [
                    'method_inflector' => 'bus2.inflector',
                    'middleware' => [
                        'my.other.middleware',
                    ],
                ],
            ],
        ]);

        $this->assertEquals(
            new BusBuilder('other', 'bus2.inflector', ['my.other.middleware']),
            $builders->getIterator()['other']
        );
    }

    public function test_default_bus_is_set()
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

        $this->assertEquals(
            new BusBuilder('default', BusBuildersFromConfig::DEFAULT_METHOD_INFLECTOR, ['my.middleware']),
            $builders->defaultBus()
        );
    }

    public function test_default_bus_can_be_overrided()
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

        $this->assertEquals(
            new BusBuilder('other', BusBuildersFromConfig::DEFAULT_METHOD_INFLECTOR, ['my.other.middleware']),
            $builders->defaultBus()
        );
    }
}
