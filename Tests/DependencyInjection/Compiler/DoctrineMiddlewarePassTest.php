<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler;

use League\Tactician\Doctrine\ORM\TransactionMiddleware;
use Mockery\MockInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use League\Tactician\Bundle\DependencyInjection\Compiler\DoctrineMiddlewarePass;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineMiddlewarePassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder|MockInterface
     */
    protected $container;

    /**
     * @var DoctrineMiddlewarePass
     */
    protected $compiler;

    protected function setUp()
    {
        parent::setUp();
        $this->container = \Mockery::mock(ContainerBuilder::class);

        $this->compiler = new DoctrineMiddlewarePass();
    }

    public function testProcess()
    {
        if (!class_exists(TransactionMiddleware::class)) {
            $this->markTestSkipped('"league/tactician-doctrine" is not installed');
        }

        $this->container->shouldReceive('hasParameter')
            ->with('doctrine.entity_managers')
            ->once()
            ->andReturn(true);

        $this->container->shouldReceive('getParameter')
            ->with('doctrine.entity_managers')
            ->once()
            ->andReturn([
                'default' => 'doctrine.orm.default_entity_manager',
                'second' => 'doctrine.orm.second_entity_manager',
            ]);

        $this->container->shouldReceive('getParameter')
            ->with('doctrine.default_entity_manager')
            ->once()
            ->andReturn('default');

        $this->container->shouldReceive('setDefinition')
            ->andReturnUsing(function($name, Definition $def) {
                \PHPUnit_Framework_Assert::assertEquals('tactician.middleware.doctrine.default', $name);

                \PHPUnit_Framework_Assert::assertEquals(TransactionMiddleware::class, $def->getClass());
                \PHPUnit_Framework_Assert::assertCount(1, $def->getArguments());
                \PHPUnit_Framework_Assert::assertInstanceOf(Reference::class, $def->getArgument(0));
                \PHPUnit_Framework_Assert::assertEquals('doctrine.orm.default_entity_manager', (string)$def->getArgument(0));
            })
            ->once();

        $this->container->shouldReceive('setDefinition')
            ->andReturnUsing(function($name, Definition $def) {
                \PHPUnit_Framework_Assert::assertEquals('tactician.middleware.doctrine.second', $name);

                \PHPUnit_Framework_Assert::assertEquals(TransactionMiddleware::class, $def->getClass());
                \PHPUnit_Framework_Assert::assertCount(1, $def->getArguments());
                \PHPUnit_Framework_Assert::assertInstanceOf(Reference::class, $def->getArgument(0));
                \PHPUnit_Framework_Assert::assertEquals('doctrine.orm.second_entity_manager', (string)$def->getArgument(0));
            })
            ->once();

        $this->container->shouldReceive('setDefinition')
            ->with('tactician.middleware.doctrine.second')
            ->once();

        $this->container->shouldReceive('setAlias')
            ->once()
            ->with('tactician.middleware.doctrine', 'tactician.middleware.doctrine.default');

        $this->compiler->process($this->container);
    }

    public function testDoNotProcessWhenThereAreNoEntityManagers()
    {
        if (!class_exists(TransactionMiddleware::class)) {
            $this->markTestSkipped('"league/tactician-doctrine" is not installed');
        }

        $this->container->shouldReceive('hasParameter')
            ->with('doctrine.entity_managers')
            ->once()
            ->andReturn(false);

        $this->container->shouldNotReceive('getParameter')
            ->withAnyArgs();

        $this->container->shouldNotReceive('setDefinition')
            ->withAnyArgs();

        $this->container->shouldNotReceive('setAlias')
            ->withAnyArgs();

        $this->compiler->process($this->container);
    }

    public function testDoNotProcessWhenTacticianDoctrineIsNotInstalled()
    {
        if (class_exists(TransactionMiddleware::class)) {
            $this->markTestSkipped('"league/tactician-doctrine" is installed');
        }

        $this->container->shouldReceive('hasParameter')
            ->with('doctrine.entity_managers')
            ->andReturn(true);

        $this->container->shouldNotReceive('getParameter')
            ->withAnyArgs();

        $this->container->shouldNotReceive('setDefinition')
            ->withAnyArgs();

        $this->container->shouldNotReceive('setAlias')
            ->withAnyArgs();

        $this->compiler->process($this->container);
    }
}
