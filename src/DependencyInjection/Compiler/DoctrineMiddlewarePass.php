<?php
namespace League\Tactician\Bundle\DependencyInjection\Compiler;

use League\Tactician\Doctrine\ORM\RollbackOnlyTransactionMiddleware;
use League\Tactician\Doctrine\ORM\TransactionMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass registers doctrine entity manager middleware
 */
class DoctrineMiddlewarePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!class_exists(TransactionMiddleware::class) || !$container->hasParameter('doctrine.entity_managers')) {
            return;
        }

        $entityManagers = $container->getParameter('doctrine.entity_managers');
        if (empty($entityManagers)) {
            return;
        }

        foreach ($entityManagers as $name => $serviceId) {
            $container->setDefinition(
                sprintf('tactician.middleware.doctrine.%s', $name),
                new Definition(TransactionMiddleware::class, [ new Reference($serviceId) ])
            );

            $container->setDefinition(
                sprintf('tactician.middleware.doctrine_rollback_only.%s', $name),
                new Definition(RollbackOnlyTransactionMiddleware::class, [ new Reference($serviceId) ])
            );
        }

        $defaultEntityManager = $container->getParameter('doctrine.default_entity_manager');
        $container->setAlias('tactician.middleware.doctrine', sprintf('tactician.middleware.doctrine.%s', $defaultEntityManager));
        $container->setAlias('tactician.middleware.doctrine_rollback_only', sprintf('tactician.middleware.doctrine_rollback_only.%s', $defaultEntityManager));
    }
}

