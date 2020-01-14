<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder;

use League\Tactician\Bundle\Handler\ContainerBasedHandlerLocator;
use League\Tactician\CommandBus;
use League\Tactician\Container\ContainerLocator;
use League\Tactician\Handler\CommandHandlerMiddleware;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class BusBuilder
{
    /**
     * @var string
     */
    private $busId;

    /**
     * @var string[]
     */
    private $middlewareIds = [];

    /**
     * @var string
     */
    private $methodInflectorId;

    public function __construct(string $busId, string $methodInflector, array $middlewareIds)
    {
        $this->busId = $busId;
        $this->methodInflectorId = $methodInflector;
        $this->middlewareIds = $middlewareIds;
    }

    public function id(): string
    {
        return $this->busId;
    }

    public function serviceId(): string
    {
        return "tactician.commandbus.$this->busId";
    }

    public function locatorServiceId()
    {
        return "tactician.commandbus.{$this->busId}.handler.locator";
    }

    public function commandHandlerMiddlewareId(): string
    {
        return "tactician.commandbus.{$this->busId}.middleware.command_handler";
    }

    public function registerInContainer(ContainerBuilder $container, array $commandsToAccept)
    {
        $this->registerLocatorService($container, $commandsToAccept);

        $container->setDefinition(
            $this->commandHandlerMiddlewareId(),
            new Definition(
                CommandHandlerMiddleware::class,
                [
                    new Reference('tactician.handler.command_name_extractor.class_name'),
                    new Reference($this->locatorServiceId()),
                    new Reference($this->methodInflectorId),
                ]
            )
        );

        $container->setDefinition(
            $this->serviceId(),
            new Definition(
                CommandBus::class,
                [
                    array_map(
                        function (string $id) { return new Reference($id); },
                        $this->middlewareIds
                    )
                ]
            )
        )->setPublic(true);

        if (method_exists($container, 'registerAliasForArgument')) {
            $container->registerAliasForArgument($this->serviceId(), CommandBus::class, "{$this->busId}Bus");
        }
    }

    private function registerLocatorService(ContainerBuilder $container, $commandsToAccept)
    {
        // Leverage symfony/dependency-injection:^3.3 service locators
        if (class_exists(ServiceLocator::class)) {
            $definition = new Definition(
                ContainerLocator::class,
                [new Reference($this->registerHandlerServiceLocator($container, $commandsToAccept)), $commandsToAccept]
            );
        } else {
            $definition = new Definition(
                ContainerBasedHandlerLocator::class,
                [new Reference('service_container'), $commandsToAccept]
            );
        }

        $container->setDefinition($this->locatorServiceId(), $definition);
    }

    private function registerHandlerServiceLocator(ContainerBuilder $container, array $commandsToAccept): string
    {
        $handlers = [];
        foreach ($commandsToAccept as $commandName => $handlerId) {
            $handlers[$handlerId] = new ServiceClosureArgument(new Reference($handlerId));
        }

        $handlerServiceLocator = (new Definition(ServiceLocator::class, [$handlers]))
            ->setPublic(false)
            ->addTag('container.service_locator');

        $container->setDefinition(
            $handlerId = "tactician.commandbus.{$this->busId}.handler.service_locator",
            $handlerServiceLocator
        );

        return $handlerId;
    }
}
