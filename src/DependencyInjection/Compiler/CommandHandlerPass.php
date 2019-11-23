<?php

namespace League\Tactician\Bundle\DependencyInjection\Compiler;

use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuildersFromConfig;
use League\Tactician\CommandBus;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function array_keys;

/**
 * This compiler pass maps Handler DI tags to specific commands.
 */
final class CommandHandlerPass implements CompilerPassInterface
{
    public const TACTICIAN_HANDLER_TAG = 'tactician.handler';

    public function process(ContainerBuilder $container) : void
    {
        $handlers = $container->findTaggedServiceIds(self::TACTICIAN_HANDLER_TAG);
        foreach ($handlers as $handler => $_) {
            $definition = $container->findDefinition($handler);
            $definition->setPublic(true);
        }

        $builders = BusBuildersFromConfig::convert(
            $this->readAndForgetParameter($container, 'tactician.merged_config')
        );

        // Register the completed builders in our container
        foreach ($builders as $builder) {
            $builder->registerInContainer($container);
        }

        // Setup default aliases
        $container->setAlias('tactician.commandbus', $builders->defaultBus()->serviceId());
        $container->setAlias(CommandBus::class, 'tactician.commandbus');
        $container->setAlias('tactician.middleware.command_handler', $builders->defaultBus()->commandHandlerMiddlewareId());

        // Wire debug command
        if ($container->hasDefinition('tactician.command.debug')) {
            $container->getDefinition('tactician.command.debug')->addArgument(array_keys($handlers));
        }
    }

    /** @return mixed */
    private function readAndForgetParameter(ContainerBuilder $container, string $parameter)
    {
        $value = $container->getParameter($parameter);
        $container->getParameterBag()->remove($parameter);

        return $value;
    }
}
