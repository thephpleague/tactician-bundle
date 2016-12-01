<?php
namespace League\Tactician\Bundle\DependencyInjection\Compiler;

use League\Tactician\Bundle\Middleware\SecurityMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass registers security middleware if possible
 */
class SecurityMiddlewarePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('security.authorization_checker')) {
            return;
        }

        $container->setDefinition(
            SecurityMiddleware::SERVICE_ID,
            new Definition(SecurityMiddleware::class, [ new Reference('security.authorization_checker') ])
        );
    }
}

