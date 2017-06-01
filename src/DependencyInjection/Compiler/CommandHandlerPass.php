<?php
namespace League\Tactician\Bundle\DependencyInjection\Compiler;

use League\Tactician\Bundle\Handler\ContainerBasedHandlerLocator;
use League\Tactician\Container\ContainerLocator;
use League\Tactician\Handler\CommandHandlerMiddleware;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * This compiler pass maps Handler DI tags to specific commands.
 */
class CommandHandlerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     *
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

        $container->setAlias(
            'tactician.handler.locator.symfony',
            'tactician.commandbus.'.$defaultBusId.'.handler.locator'
        );

        $container->setAlias(
            'tactician.middleware.command_handler',
            'tactician.commandbus.'.$defaultBusId.'.middleware.command_handler'
        );

        foreach ($busIds as $busId) {
            $locatorServiceId = 'tactician.commandbus.'.$busId.'.handler.locator';
            $methodInflectorId = $container->getParameter(sprintf('tactician.method_inflector.%s', $busId));
            $handlerMapping = array_key_exists($busId, $busIdToHandlerMapping) ? $busIdToHandlerMapping[$busId] : [];

            // Leverage symfony/dependency-injection:^3.3 service locators
            if (class_exists(ServiceLocator::class)) {
                $serviceLocatorId = 'tactician.commandbus.'.$busId.'.handler.service_locator';
                $this->registerHandlerServiceLocator($container, $serviceLocatorId, $handlerMapping);
                $locatorDefinition = $this->buildLocatorDefinition($handlerMapping, ContainerLocator::class, $serviceLocatorId);
            } else {
                $locatorDefinition = $this->buildLocatorDefinition($handlerMapping);
            }

            $container->setDefinition(
                $locatorServiceId,
                $locatorDefinition
            );

            $container->setDefinition(
                'tactician.commandbus.'.$busId.'.middleware.command_handler',
                new Definition(
                    CommandHandlerMiddleware::class,
                    [
                        new Reference('tactician.handler.command_name_extractor.class_name'),
                        new Reference($locatorServiceId),
                        new Reference($methodInflectorId),
                    ]
                )
            );
            $this->guardInvalidMiddlewares($container, $busId);
        }
    }

    /**
     * @param string $id
     * @param array  $busIds
     *
     * @throws \Exception
     */
    protected function abortIfInvalidBusId($id, array $busIds)
    {
        if (!in_array($id, $busIds)) {
            throw new \Exception('Invalid bus id "'.$id.'". Valid buses are: '.implode(', ', $busIds));
        }
    }

    /**
     * @param array  $handlerMapping
     * @param string $locatorServiceClass
     * @param string $locatorServiceId
     *
     * @return Definition
     */
    protected function buildLocatorDefinition(
        array $handlerMapping,
        $locatorServiceClass = ContainerBasedHandlerLocator::class,
        $locatorServiceId = 'service_container'
    ) {
        return new Definition(
            $locatorServiceClass,
            [
                new Reference($locatorServiceId),
                $handlerMapping,
            ]
        );
    }

    private function guardInvalidMiddlewares(ContainerBuilder $container, $busId)
    {
        $busDefinition = $container->getDefinition('tactician.commandbus.'.$busId);
        foreach ($busDefinition->getArgument(0) as $middlewareReference) {
            if (false === $container->has($middlewareReference)) {
                throw UnknownMiddlewareException::withId((string) $middlewareReference);
            }
        }
    }

    private function registerHandlerServiceLocator(ContainerBuilder $container, $serviceLocatorId, array $handlerMapping)
    {
        $handlers = [];
        foreach ($handlerMapping as $commandName => $id) {
            $handlers[$id] = new ServiceClosureArgument(new Reference($id));
        }

        $handlerServiceLocator = (new Definition(ServiceLocator::class, [$handlers]))
            ->setPublic(false)
            ->addTag('container.service_locator')
        ;

        $container->setDefinition($serviceLocatorId, $handlerServiceLocator);
    }
}
