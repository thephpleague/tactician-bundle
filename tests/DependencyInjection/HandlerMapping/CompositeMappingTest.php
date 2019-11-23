<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection\HandlerMapping;

use League\Tactician\Bundle\DependencyInjection\HandlerMapping\CompositeMapping;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\HandlerMapping;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use League\Tactician\Bundle\Tests\Fake\OtherFakeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CompositeMappingTest extends TestCase
{
    public function test_merging_multiple_mappings()
    {
        $a = new MockMapping(FakeCommand::class, 'fake.command.handler');
        $b = new MockMapping(OtherFakeCommand::class, 'other.fake.command.handler');

        $finalRouting = (new CompositeMapping($a, $b))->build(
            new ContainerBuilder(),
            new Routing(['default_bus_id'])
        );

        $this->assertEquals(
            [
                FakeCommand::class => 'fake.command.handler',
                OtherFakeCommand::class => 'other.fake.command.handler'
            ],
            $finalRouting->commandToServiceMapping('default_bus_id')
        );
    }
}

class MockMapping implements HandlerMapping
{
    private $fqcn;
    private $serviceId;

    public function __construct($fqcn, $serviceId)
    {
        $this->fqcn = $fqcn;
        $this->serviceId = $serviceId;
    }

    public function build(ContainerBuilder $container, Routing $routing): Routing
    {
        $routing->routeToAllBuses($this->fqcn, $this->serviceId);
        return $routing;
    }
}
