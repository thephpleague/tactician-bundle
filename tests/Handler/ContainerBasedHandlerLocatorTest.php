<?php

namespace League\Tactician\Bundle\Tests\Handler;

use League\Tactician\Bundle\Handler\ContainerBasedHandlerLocator;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerBasedHandlerLocatorTest extends TestCase
{

    /**
     * @var ContainerInterface | MockInterface
     */
    protected $container;

    /**
     * @var ContainerBasedHandlerLocator
     */
    protected $locator;

    protected function setUp()
    {
        parent::setUp();

        $this->container = \Mockery::mock(ContainerInterface::class);
    }

    public function testGetHandler()
    {
        $commandName = 'MyFakeCommand';
        $serviceId   = 'my_bundle.service.id';

        $definitions = [
            $commandName   => $serviceId,
            'OtherCommand' => 'my_bundle.order.id'
        ];

        $this->container->shouldReceive('get')
            ->with($serviceId)
            ->once()
            ->andReturn($serviceId);

        $this->locator = new ContainerBasedHandlerLocator($this->container, $definitions);
        $result        = $this->locator->getHandlerForCommand($commandName);

        $this->assertEquals($serviceId, $result);
    }

    /**
     * @expectedException \League\Tactician\Exception\MissingHandlerException
     */
    public function testGetHandlerThrowsExceptionForNotFound()
    {
        $definitions = [
            'OtherCommand' => 'my_bundle.order.id'
        ];

        $this->container->shouldReceive('get')
            ->never();

        $this->locator = new ContainerBasedHandlerLocator($this->container, $definitions);
        $this->locator->getHandlerForCommand('MyFakeCommand');
    }

}
