<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection\HandlerMapping;

use League\Tactician\Bundle\DependencyInjection\HandlerMapping\ClassNameMapping;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;
use League\Tactician\Bundle\DependencyInjection\InvalidCommandBusId;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use League\Tactician\Bundle\Tests\Fake\OtherFakeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class ClassNameMappingTest extends TestCase
{
    public function test_will_skip_definitions_without_command_tag()
    {
        $builder = new ContainerBuilder();
        $builder
            ->setDefinition('some.handler', new Definition(SomeHandler::class))
            ->addTag('tactician.handler', ['foo' => 'bar']);

        $routing = (new ClassNameMapping())->build($builder, new Routing(['default']));

        $this->assertEquals([], $routing->commandToServiceMapping('default'));
    }

    public function test_will_find_handler_for_defined_command()
    {
        $builder = new ContainerBuilder();
        $builder
            ->setDefinition('some.handler', new Definition(SomeHandler::class))
            ->addTag('tactician.handler', ['command' => FakeCommand::class]);

        $routing = (new ClassNameMapping())->build($builder, new Routing(['default']));

        $this->assertEquals([FakeCommand::class => 'some.handler'], $routing->commandToServiceMapping('default'));
    }

    public function test_can_bind_to_specific_bus()
    {
        $builder = new ContainerBuilder();
        $builder
            ->setDefinition('first.handler', new Definition(SomeHandler::class))
            ->addTag('tactician.handler', ['command' => FakeCommand::class, 'bus' => 'bus.a']);

        $builder
            ->setDefinition('second.handler', new Definition(SomeHandler::class))
            ->addTag('tactician.handler', ['command' => OtherFakeCommand::class, 'bus' => 'bus.b']);

        $routing = (new ClassNameMapping())->build($builder, new Routing(['bus.a', 'bus.b']));

        $this->assertEquals(
            [
                FakeCommand::class => 'first.handler',
            ],
            $routing->commandToServiceMapping('bus.a')
        );
        $this->assertEquals(
            [OtherFakeCommand::class => 'second.handler'],
            $routing->commandToServiceMapping('bus.b')
        );
    }

    public function test_can_bind_to_multiple_buses()
    {
        $builder = new ContainerBuilder();
        $builder
            ->setDefinition('first.handler', new Definition(SomeHandler::class))
            ->addTag('tactician.handler', ['command' => FakeCommand::class, 'bus' => 'bus.a'])
            ->addTag('tactician.handler', ['command' => FakeCommand::class, 'bus' => 'bus.b']);

        $routing = (new ClassNameMapping())->build($builder, new Routing(['bus.a', 'bus.b']));

        $this->assertEquals([FakeCommand::class => 'first.handler'], $routing->commandToServiceMapping('bus.a'));
        $this->assertEquals([FakeCommand::class => 'first.handler'], $routing->commandToServiceMapping('bus.b'));
    }

    public function test_will_error_when_given_invalid_bus()
    {
        $this->expectException(InvalidCommandBusId::class);

        $builder = new ContainerBuilder();
        $builder
            ->setDefinition('first.handler', new Definition(SomeHandler::class))
            ->addTag('tactician.handler', ['command' => FakeCommand::class, 'bus' => 'bus.does.not.exist.mwhahahaha']);

        (new ClassNameMapping())->build($builder, new Routing(['bus.a', 'bus.b']));
    }
}

class SomeHandler
{
    public function handle($foo)
    {
    }
}
