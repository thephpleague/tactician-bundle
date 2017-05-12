<?php
namespace League\Tactician\Bundle\DependencyInjection\Compiler;

use League\Tactician\Bundle\Middleware\ValidatorMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass registers validator middleware if possible
 */
class ValidatorMiddlewarePass implements CompilerPassInterface
{
    const SERVICE_ID = 'tactician.middleware.validator';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('validator')) {
            return;
        }

        $container->setDefinition(
            static::SERVICE_ID,
            new Definition(ValidatorMiddleware::class, [ new Reference('validator') ])
        );
    }
}

