<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection\HandlerMapping;

use DateTime;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\TypeHintMapping;
use League\Tactician\Bundle\DependencyInjection\InvalidCommandBusId;
use League\Tactician\Bundle\Tests\Fake\FakeCommand;
use League\Tactician\Bundle\Tests\Fake\OtherFakeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class TypeHintMappingTest extends TestCase
{
    public function test_will_skip_definitions_without_auto_tag()
    {
        $builder = new ContainerBuilder();
        $builder
            ->setDefinition('some.handler', new Definition(InvokeHandler::class))
            ->addTag('tactician.handler', ['foo' => 'bar']);

        $routing = (new TypeHintMapping())->build($builder, new Routing(['default']));

        $this->assertEquals([], $routing->commandToServiceMapping('default'));
    }

    public function test_will_resolve_parameters_in_handler_class()
    {
        $builder = new ContainerBuilder();
        $builder->setParameter('handler_class', InvokeHandler::class);
        $builder
            ->setDefinition('some.handler', new Definition('%handler_class%'))
            ->addTag('tactician.handler', ['typehints' => true]);

        $routing = (new TypeHintMapping())->build($builder, new Routing(['default']));

        $this->assertEquals([FakeCommand::class => 'some.handler'], $routing->commandToServiceMapping('default'));
    }

    /**
     * @dataProvider simpleTestCases
     */
    public function test_standard(string $handlerFQCN, array $expectedMapping)
    {
        $builder = new ContainerBuilder();
        $builder
            ->setDefinition('some.handler', new Definition($handlerFQCN))
            ->addTag('tactician.handler', ['typehints' => true]);

        $routing = (new TypeHintMapping())->build($builder, new Routing(['default']));

        $this->assertEquals($expectedMapping, $routing->commandToServiceMapping('default'));
    }

    public function simpleTestCases()
    {
        return [
            'can read __invoke magic method type hint' => [
                InvokeHandler::class,
                [FakeCommand::class => 'some.handler']
            ],
            'takes unary methods but not those with multiple parameters' => [
                BasicHandler::class,
                [FakeCommand::class => 'some.handler', OtherFakeCommand::class => 'some.handler']
            ],
            'can not exclude built-in objects unfortunately' => [
                DateTimeHandler::class,
                [DateTime::class => 'some.handler']
            ],
            'will skip methods with no typehint' => [NoTypehintHandler::class, []],
            'will skip methods with an interface typehint' => [InterfaceTypehintHandler::class, []],
            'will not try to map scalar typehints' => [ScalarHandler::class, []],
            'will not use protected or private methods' => [ProtectedMethodHandler::class, []],
            'will not use constructor method' => [ConstructorHandler::class, []],
            'will not use static methods' => [StaticHandler::class, []],
            'will not use abstract methods' => [AbstractHandler::class, []],
            'will not use variadic methods' => [VariadicHandler::class, []]
        ];
    }

    public function test_can_bind_to_specific_bus()
    {
        $builder = new ContainerBuilder();
        $builder
            ->setDefinition('first.handler', new Definition(BasicHandler::class))
            ->addTag('tactician.handler', ['typehints' => true, 'bus' => 'bus.a']);

        $builder
            ->setDefinition('second.handler', new Definition(DateTimeHandler::class))
            ->addTag('tactician.handler', ['typehints' => true, 'bus' => 'bus.b']);

        $routing = (new TypeHintMapping())->build($builder, new Routing(['bus.a', 'bus.b']));

        $this->assertEquals(
            [
                FakeCommand::class => 'first.handler',
                OtherFakeCommand::class => 'first.handler'
            ],
            $routing->commandToServiceMapping('bus.a')
        );
        $this->assertEquals(
            [DateTime::class => 'second.handler'],
            $routing->commandToServiceMapping('bus.b')
        );
    }

    public function test_can_bind_to_multiple_buses()
    {
        $builder = new ContainerBuilder();
        $builder
            ->setDefinition('first.handler', new Definition(BasicHandler::class))
            ->addTag('tactician.handler', ['typehints' => true, 'bus' => 'bus.a'])
            ->addTag('tactician.handler', ['typehints' => true, 'bus' => 'bus.b']);

        $routing = (new TypeHintMapping())->build($builder, new Routing(['bus.a', 'bus.b']));

        $expected = [
            FakeCommand::class => 'first.handler',
            OtherFakeCommand::class => 'first.handler',
        ];

        $this->assertEquals($expected, $routing->commandToServiceMapping('bus.a'));
        $this->assertEquals($expected, $routing->commandToServiceMapping('bus.b'));
    }

    public function test_will_error_when_given_invalid_bus()
    {
        $this->expectException(InvalidCommandBusId::class);

        $builder = new ContainerBuilder();
        $builder
            ->setDefinition('first.handler', new Definition(BasicHandler::class))
            ->addTag('tactician.handler', ['typehints' => true, 'bus' => 'bus.does.not.exist.mwhahahaha']);

        (new TypeHintMapping())->build($builder, new Routing(['bus.a', 'bus.b']));
    }
}

class BasicHandler
{
    public function handle(FakeCommand $command)
    {
    }

    public function run(OtherFakeCommand $command)
    {
    }

    public function notACommand(FakeCommand $cmdA, OtherFakeCommand $cmdB)
    {
    }
}

class VariadicHandler
{
    public function handle(FakeCommand ...$commands)
    {
    }
}

class DateTimeHandler
{
    public function handle(DateTime $command)
    {
    }
}

class StaticHandler
{
    public static function handle(FakeCommand $command)
    {
    }
}

abstract class AbstractHandler
{
    abstract public function handle(FakeCommand $command);
}

class ScalarHandler
{
    public function handle(string $someString)
    {
    }

    public function execute(int $foobar)
    {
    }

    public function that(callable $thing)
    {
    }
}

interface ServiceInterface {

}

class InterfaceTypehintHandler
{
    public function interfaced(ServiceInterface $foo)
    {
    }
}

class NoTypehintHandler
{
    public function handle($foo)
    {
    }
}

class InvokeHandler
{
    public function __invoke(FakeCommand $command)
    {
    }
}


class ProtectedMethodHandler
{
    protected function handle(FakeCommand $command)
    {
    }

    private function execute(OtherFakeCommand $command)
    {
    }
}

class ConstructorHandler
{
    public function __construct(SomeDependency $dependency)
    {
    }
}

class SomeDependency
{
}
