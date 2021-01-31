<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler;

use League\Tactician\Bundle\DependencyInjection\Compiler\DoctrineMiddlewarePass;
use League\Tactician\Doctrine\ORM\RollbackOnlyTransactionMiddleware;
use League\Tactician\Doctrine\ORM\TransactionMiddleware;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineMiddlewarePassTest extends AbstractCompilerPassTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->container->set('doctrine.orm.default_entity_manager', new stdClass());
        $this->container->set('doctrine.orm.second_entity_manager', new stdClass());
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DoctrineMiddlewarePass());
    }

    public function test_registering_middleware_for_multiple_entity_managers()
    {
        if (!class_exists(TransactionMiddleware::class)) {
            $this->markTestSkipped('"league/tactician-doctrine" is not installed');
        }

        $this->setParameter(
            'doctrine.entity_managers',
            [
                'default' => 'doctrine.orm.default_entity_manager',
                'second' => 'doctrine.orm.second_entity_manager',
            ]
        );
        $this->setParameter('doctrine.default_entity_manager', 'default');

        $this->compile();

        $this->assertContainerBuilderHasService('tactician.middleware.doctrine.default', TransactionMiddleware::class);
        $this->assertContainerBuilderHasService('tactician.middleware.doctrine.second', TransactionMiddleware::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('tactician.middleware.doctrine.default', 0, new Reference('doctrine.orm.default_entity_manager'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('tactician.middleware.doctrine.second', 0, new Reference('doctrine.orm.second_entity_manager'));
        $this->assertContainerBuilderHasAlias('tactician.middleware.doctrine', 'tactician.middleware.doctrine.default');
    }

    public function test_do_not_process_when_there_are_no_entity_managers()
    {
        if (!class_exists(TransactionMiddleware::class)) {
            $this->markTestSkipped('"league/tactician-doctrine" is not installed');
        }

        $this->compile();

        $this->assertContainerBuilderNotHasService('tactician.middleware.doctrine');
    }

    public function test_do_not_process_when_tactician_doctrine_is_not_installed()
    {
        if (class_exists(TransactionMiddleware::class)) {
            $this->markTestSkipped('"league/tactician-doctrine" is installed');
        }

        $this->setParameter('doctrine.entity_managers', ['default' => 'doctrine.orm.default_entity_manager']);
        $this->setParameter('doctrine.default_entity_manager', 'default');

        $this->compile();

        $this->assertContainerBuilderNotHasService('tactician.middleware.doctrine');
    }

    public function test_rollback_only_middleware_is_added()
    {
        if (!class_exists(RollbackOnlyTransactionMiddleware::class)) {
            $this->markTestSkipped('"league/tactician-doctrine" is not installed');
        }

        $this->setParameter(
            'doctrine.entity_managers',
            [
                'default' => 'doctrine.orm.default_entity_manager',
                'second' => 'doctrine.orm.second_entity_manager',
            ]
        );
        $this->setParameter('doctrine.default_entity_manager', 'default');

        $this->compile();

        $this->assertContainerBuilderHasService('tactician.middleware.doctrine_rollback_only.default', RollbackOnlyTransactionMiddleware::class);
        $this->assertContainerBuilderHasService('tactician.middleware.doctrine_rollback_only.second', RollbackOnlyTransactionMiddleware::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('tactician.middleware.doctrine_rollback_only.default', 0, new Reference('doctrine.orm.default_entity_manager'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('tactician.middleware.doctrine_rollback_only.second', 0, new Reference('doctrine.orm.second_entity_manager'));
        $this->assertContainerBuilderHasAlias('tactician.middleware.doctrine_rollback_only', 'tactician.middleware.doctrine_rollback_only.default');
    }
}
