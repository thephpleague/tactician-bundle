<?php


namespace Xtrasmal\TacticianBundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\AbstractConfigurationTestCase;
use Xtrasmal\TacticianBundle\DependencyInjection\Configuration;

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
        $this->assertConfigurationIsValid([
            'tactician' => [
                'default_bus' => 'foo',
                'commandbus' => [
                    'bar' => [
                        'middleware' => [

                        ]
                    ]
                ]
            ]
        ]);
        $this->markTestIncomplete('Not sure how to implement this with configuration invalidation.');
    }
}
