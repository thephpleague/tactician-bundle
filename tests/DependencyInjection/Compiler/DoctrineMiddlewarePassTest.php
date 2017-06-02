<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\Compiler;

use League\Tactician\Bundle\DependencyInjection\Compiler\DoctrineMiddlewarePass;
use League\Tactician\Doctrine\ORM\TransactionMiddleware;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineMiddlewarePassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
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
}
