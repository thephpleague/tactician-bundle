<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler;

use League\Tactician\Bundle\DependencyInjection\Compiler\DoctrineMiddlewarePass;
use League\Tactician\Doctrine\ORM\RollbackOnlyTransactionMiddleware;
use League\Tactician\Doctrine\ORM\TransactionMiddleware;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineMiddlewarePassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->set('doctrine.orm.default_entity_manager', new stdClass());
        $this->container->set('doctrine.orm.second_entity_manager', new stdClass());
        $this->container->addCompilerPass(new DoctrineMiddlewarePass());
    }

    private function compile(): void
    {
        $this->container->compile();
    }

    public function test_registering_middleware_for_multiple_entity_managers()
    {
        if (!class_exists(TransactionMiddleware::class)) {
            $this->markTestSkipped('"league/tactician-doctrine" is not installed');
        }

        $this->container->setParameter(
            'doctrine.entity_managers',
            [
                'default' => 'doctrine.orm.default_entity_manager',
                'second' => 'doctrine.orm.second_entity_manager',
            ]
        );
        $this->container->setParameter('doctrine.default_entity_manager', 'default');

        $this->compile();

        $this->assertTrue($this->container->hasDefinition('tactician.middleware.doctrine.default'));
        $this->assertEquals(TransactionMiddleware::class, $this->container->getDefinition('tactician.middleware.doctrine.default')->getClass());
        $this->assertTrue($this->container->hasDefinition('tactician.middleware.doctrine.second'));
        $this->assertEquals(TransactionMiddleware::class, $this->container->getDefinition('tactician.middleware.doctrine.second')->getClass());
        $this->assertEquals(new Reference('doctrine.orm.default_entity_manager'), $this->container->getDefinition('tactician.middleware.doctrine.default')->getArgument(0));
        $this->assertEquals(new Reference('doctrine.orm.second_entity_manager'), $this->container->getDefinition('tactician.middleware.doctrine.second')->getArgument(0));
        $this->assertTrue($this->container->hasAlias('tactician.middleware.doctrine'));
        $this->assertEquals('tactician.middleware.doctrine.default', (string) $this->container->getAlias('tactician.middleware.doctrine'));
    }

    public function test_do_not_process_when_there_are_no_entity_managers()
    {
        if (!class_exists(TransactionMiddleware::class)) {
            $this->markTestSkipped('"league/tactician-doctrine" is not installed');
        }

        $this->compile();

        $this->assertFalse($this->container->hasDefinition('tactician.middleware.doctrine'));
    }

    public function test_do_not_process_when_tactician_doctrine_is_not_installed()
    {
        if (class_exists(TransactionMiddleware::class)) {
            $this->markTestSkipped('"league/tactician-doctrine" is installed');
        }

        $this->container->setParameter('doctrine.entity_managers', ['default' => 'doctrine.orm.default_entity_manager']);
        $this->container->setParameter('doctrine.default_entity_manager', 'default');

        $this->compile();

        $this->assertFalse($this->container->hasDefinition('tactician.middleware.doctrine'));
    }

    public function test_rollback_only_middleware_is_added()
    {
        if (!class_exists(RollbackOnlyTransactionMiddleware::class)) {
            $this->markTestSkipped('"league/tactician-doctrine" is not installed');
        }

        $this->container->setParameter(
            'doctrine.entity_managers',
            [
                'default' => 'doctrine.orm.default_entity_manager',
                'second' => 'doctrine.orm.second_entity_manager',
            ]
        );
        $this->container->setParameter('doctrine.default_entity_manager', 'default');

        $this->compile();

        $this->assertTrue($this->container->hasDefinition('tactician.middleware.doctrine_rollback_only.default'));
        $this->assertEquals(RollbackOnlyTransactionMiddleware::class, $this->container->getDefinition('tactician.middleware.doctrine_rollback_only.default')->getClass());
        $this->assertTrue($this->container->hasDefinition('tactician.middleware.doctrine_rollback_only.second'));
        $this->assertEquals(RollbackOnlyTransactionMiddleware::class, $this->container->getDefinition('tactician.middleware.doctrine_rollback_only.second')->getClass());
        $this->assertEquals(new Reference('doctrine.orm.default_entity_manager'), $this->container->getDefinition('tactician.middleware.doctrine_rollback_only.default')->getArgument(0));
        $this->assertEquals(new Reference('doctrine.orm.second_entity_manager'), $this->container->getDefinition('tactician.middleware.doctrine_rollback_only.second')->getArgument(0));
        $this->assertTrue($this->container->hasAlias('tactician.middleware.doctrine_rollback_only'));
        $this->assertEquals('tactician.middleware.doctrine_rollback_only.default', (string) $this->container->getAlias('tactician.middleware.doctrine_rollback_only'));
    }
}
