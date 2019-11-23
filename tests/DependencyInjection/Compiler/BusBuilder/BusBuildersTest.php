<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler\BusBuilder;

use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilder;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilders;
use League\Tactician\Bundle\DependencyInjection\DuplicatedCommandBusId;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;
use League\Tactician\Bundle\DependencyInjection\InvalidCommandBusId;
use PHPUnit\Framework\TestCase;

final class BusBuildersTest extends TestCase
{
    public function test_can_iterate_over_builders()
    {
        $builders = new BusBuilders(
            [$a, $b] = $this->buildersNamed('foo', 'bar'),
            'foo'
        );

        $this->assertEquals(['foo' => $a, 'bar' => $b], iterator_to_array($builders));
    }

    public function test_default_builder_must_be_an_id_that_actually_exists()
    {
        $this->expectException(InvalidCommandBusId::class);

        $this->builders(['bus1'], 'some_bus_that_does_not_exist');
    }

    public function test_two_buses_can_not_have_the_same_id()
    {
        $this->expectException(DuplicatedCommandBusId::class);

        $this->builders(['bus1', 'bus1']);
    }

    public function test_blank_routing_has_ids()
    {
        $builders = $this->builders(['bus1', 'bus2']);

        $this->assertEquals(new Routing(['bus1', 'bus2']), $builders->createBlankRouting());
    }

    private function builders($ids, $default = 'bus1'): BusBuilders
    {
        return new BusBuilders($this->buildersNamed(...$ids), $default);
    }

    private function buildersNamed(string ...$ids): array
    {
        return array_map(
            function (string $id) {
                return new BusBuilder($id, 'some.inflector', []);
            },
            $ids
        );
    }
}
