<?php
namespace League\Tactician\Bundle\DependencyInjection\Compiler;

use League\Tactician\Bundle\Handler\ContainerBasedHandlerLocator;
use League\Tactician\Handler\CommandHandlerMiddleware;
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
        $defaultBusId = $container->getParameter('tactician.commandbus.default');
        $busIds = $container->getParameter('tactician.commandbus.ids');
        $busIdToHandlerMapping = [];

        foreach ($container->findTaggedServiceIds('tactician.handler') as $id => $tags) {

            foreach ($tags as $attributes) {
                if (!isset($attributes['command'])) {
                    throw new \Exception('The tactician.handler tag must always have a command attribute');
                }

                if (array_key_exists('bus', $attributes)) {
                    $this->abortIfInvalidBusId($attributes['bus'], $busIds);
                }

                $busIdsDefined = array_key_exists('bus', $attributes) ? [$attributes['bus']] : $busIds;
                foreach ($busIdsDefined as $busId) {
                    $busIdToHandlerMapping[$busId][$attributes['command']] = $id;
                }
            }
        }

        foreach ($busIds as $busId) {
            $locatorServiceId = 'tactician.commandbus.'.$busId.'.handler.locator';
            $methodInflectorId = $container->getParameter(sprintf('tactician.method_inflector.%s', $busId));
            $container->setDefinition(
                $locatorServiceId,
                $this->buildLocatorDefinition(
                    // Build an empty locator if no command defined. To be sure extension still working
                    array_key_exists($busId, $busIdToHandlerMapping) ? $busIdToHandlerMapping[$busId] : []
                )
            );

            $container->setDefinition(
                'tactician.commandbus.'.$busId.'.middleware.command_handler',
                new Definition(
                    CommandHandlerMiddleware::class,
                    [
                        new Reference('tactician.handler.command_name_extractor.class_name'),
                        new Reference($locatorServiceId),
                        new Reference($methodInflectorId)
                    ]
                )
            );
        }

        $container->setAlias(
            'tactician.handler.locator.symfony',
            'tactician.commandbus.'.$defaultBusId.'.handler.locator'
        );

        $container->setAlias(
            'tactician.middleware.command_handler',
            'tactician.commandbus.'.$defaultBusId.'.middleware.command_handler'
        );
    }

    /**
     * @param string $id
     * @param array $busIds
     * @throws Exception
     */
    protected function abortIfInvalidBusId($id, array $busIds)
    {
        if (!in_array($id, $busIds)) {
            throw new \Exception('Invalid bus id "'.$id.'". Valid buses are: '.implode(', ', $busIds));
        }
    }

    /**
     * @param array $handlerMapping
     * @return Definition
     */
    protected function buildLocatorDefinition(array $handlerMapping)
    {
        return new Definition(
            ContainerBasedHandlerLocator::class,
            [
                new Reference('service_container'),
                $handlerMapping,
            ]
        );
    }
}
