<?php

namespace League\Tactician\Bundle\DependencyInjection\Compiler;

use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilder;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuildersFromConfig;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\HandlerMapping;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This compiler pass maps Handler DI tags to specific commands.
 */
class CommandHandlerPass implements CompilerPassInterface
{
    /**
     * @var HandlerMapping
     */
    private $handlerMapping;

    public function __construct(HandlerMapping $mappingStrategy)
    {
        $this->handlerMapping = $mappingStrategy;
    }

    public function process(ContainerBuilder $container)
    {
        $builders = BusBuildersFromConfig::convert($container->getExtensionConfig('tactician')[0]);

        $routing = $this->handlerMapping->build(
            $container,
            new Routing($builders->getBusIds())
        );

        // Register the completed builders in our container
        foreach ($builders as $builder) {
            /** @var BusBuilder $builder */
            $builder->registerInContainer($container, $routing->commandToServiceMapping($builder->id()));
        }

        // Setup default aliases
        $container->setAlias('tactician.commandbus', $builders->defaultBus()->serviceId());
        $container->setAlias('tactician.handler.locator.symfony', $builders->defaultBus()->locatorServiceId());
        $container->setAlias('tactician.middleware.command_handler', $builders->defaultBus()->commandHandlerMiddlewareId());
    }
}
