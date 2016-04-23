<?php
namespace League\Tactician\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass maps Handler DI tags to specific commands
 */
class CommandHandlerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('tactician.handler.locator.symfony')) {
            throw new \Exception('Missing tactician.handler.locator.symfony definition');
        }

        $handlerLocator = $container->findDefinition('tactician.handler.locator.symfony');

        $defaultMapping = [];
        $busIdToHandlerMapping = [];

        foreach ($container->findTaggedServiceIds('tactician.handler') as $id => $tags) {

            foreach ($tags as $attributes) {
                if (!isset($attributes['command'])) {
                    throw new \Exception('The tactician.handler tag must always have a command attribute');
                }

                if (isset($attributes['bus'])) {
                    $this->abortIfInvalidBusId($attributes['bus'], $container);
                    $busIdToHandlerMapping[$attributes['bus']][$attributes['command']] = $id;
                } else {
                    $defaultMapping[$attributes['command']] = $id;
                }
            }
        }

        foreach ($busIdToHandlerMapping as $busId => $handlerMapping) {
            $container->setDefinition(
                'tactician.handler.locator.symfony.'.$busId,
                $this->buildLocatorDefinition($handlerMapping)
            );
        }

        $handlerLocator->addArgument($defaultMapping);
    }

    /**
     * @param string $id
     * @param ContainerBuilder $container 
     * @throws Exception
     */
    protected function abortIfInvalidBusId($id, ContainerBuilder $container)
    {
        $config = $container->getExtensionConfig('tactician');

        if (!array_key_exists($id, $config['commandbus'])) {
            throw new \Exception('Invalid bus id "'.$id.'". Valid buses are: '.implode(', ', array_keys($config['commandbus'])));
        }
    }

    /**
     * @param array $handlerMapping 
     * @return Definition
     */
    protected function buildLocatorDefinition(array $handlerMapping)
    {
        return new Definition(
            'League\Tactician\Bundle\Handler\ContainerBasedHandlerLocator',
            [
                new Reference('service_container'),
                $handlerMapping,
            ]
        );
    }

}
