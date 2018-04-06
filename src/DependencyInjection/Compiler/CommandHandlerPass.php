<?php

namespace League\Tactician\Bundle\DependencyInjection\Compiler;

use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuildersFromConfig;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\HandlerMapping;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use League\Tactician\CommandBus;

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
        $builders = BusBuildersFromConfig::convert(
            $this->readAndForgetParameter($container, 'tactician.merged_config')
        );

        $routing = $this->handlerMapping->build($container, $builders->createBlankRouting());

        $mappings = [];

        // Register the completed builders in our container
        foreach ($builders as $builder) {
            $commandToServiceMapping = $routing->commandToServiceMapping($builder->id());
            $mappings[$builder->id()] = $commandToServiceMapping;
            $builder->registerInContainer($container, $commandToServiceMapping);
        }

        // Setup default aliases
        $container->setAlias('tactician.commandbus', $builders->defaultBus()->serviceId());
        $container->setAlias(CommandBus::class, 'tactician.commandbus');
        $container->setAlias('tactician.handler.locator.symfony', $builders->defaultBus()->locatorServiceId());
        $container->setAlias('tactician.middleware.command_handler', $builders->defaultBus()->commandHandlerMiddlewareId());

        // Wire debug command
        if ($container->hasDefinition('tactician.command.debug')) {
            $container->getDefinition('tactician.command.debug')->addArgument($mappings);
        }
    }

    private function readAndForgetParameter(ContainerBuilder $container, $parameter)
    {
        $value = $container->getParameter($parameter);
        $container->getParameterBag()->remove($parameter);

        return $value;
    }
}
