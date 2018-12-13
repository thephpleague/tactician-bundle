<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\HandlerMapping;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

abstract class TagBasedMapping implements HandlerMapping
{
    const TAG_NAME = 'tactician.handler';

    public function build(ContainerBuilder $container, Routing $routing): Routing
    {
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $this->mapServiceByTag($container, $routing, $serviceId, $attributes);
            }
        }

        return $routing;
    }

    /**
     * @param ContainerBuilder $container
     * @param Routing $routing
     * @param $serviceId
     * @param $attributes
     */
    private function mapServiceByTag(ContainerBuilder $container, Routing $routing, $serviceId, $attributes)
    {
        $definition = $container->getDefinition($serviceId);

        if (!$this->isSupported($container, $definition, $attributes)) {
            return;
        }

        foreach ($this->findCommandsForService($container, $definition, $attributes) as $commandClassName) {
            if (isset($attributes['bus'])) {
                $routing->routeToBus($attributes['bus'], $commandClassName, $serviceId);
            } else {
                $routing->routeToAllBuses($commandClassName, $serviceId);
            }
        }
    }

    abstract protected function isSupported(ContainerBuilder $container, Definition $definition, array $tagAttributes): bool;

    abstract protected function findCommandsForService(ContainerBuilder $container, Definition $definition, array $tagAttributes): array;
}
