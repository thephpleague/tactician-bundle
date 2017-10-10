<?php

namespace League\Tactician\Bundle\DependencyInjection\Compiler;

use League\Tactician\Bundle\Command\DebugMappingCommand;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilder;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuilders;
use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuildersFromConfig;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\HandlerMapping;
use League\Tactician\Bundle\DependencyInjection\HandlerMapping\Routing;
use League\Tactician\Bundle\DependencyInjection\RoutingDebugReport;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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

        $routing = $this->handlerMapping->build($container, $builders->createBlankRouting());

        // Register the completed builders in our container
        foreach ($builders as $builder) {
            /** @var BusBuilder $builder */
            $builder->registerInContainer($container, $routing->commandToServiceMapping($builder->id()));
        }

        $this->setDebugReport($container, $builders, $routing);

        // Setup default aliases
        $container->setAlias('tactician.commandbus', $builders->defaultBus()->serviceId());
        $container->setAlias('tactician.handler.locator.symfony', $builders->defaultBus()->locatorServiceId());
        $container->setAlias('tactician.middleware.command_handler', $builders->defaultBus()->commandHandlerMiddlewareId());
    }

    private function setDebugReport(ContainerBuilder $container, BusBuilders $builders, Routing $routing)
    {
        $report = new RoutingDebugReport($builders, $routing);
        $container->set('tactician.debug.report', $report);

//        $command = (new Definition(DebugMappingCommand::class, [new Reference('tactician.debug.report')]))
//            ->addTag('console.command');
//
//        $container->setDefinition(
//            "tactician.command.debug_mapping",
//            $command
//        );
    }
}
