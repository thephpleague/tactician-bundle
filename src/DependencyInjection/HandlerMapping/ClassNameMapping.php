<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\HandlerMapping;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class ClassNameMapping extends TagBasedMapping
{
    protected function isSupported(ContainerBuilder $container, Definition $definition, array $tagAttributes): bool
    {
        return isset($tagAttributes['command']) && class_exists($container->getParameterBag()->resolveValue($tagAttributes['command']));
    }

    protected function findCommandsForService(ContainerBuilder $container, Definition $definition, array $tagAttributes): array
    {
        return [
            $container->getParameterBag()->resolveValue($tagAttributes['command'])
        ];
    }
}
