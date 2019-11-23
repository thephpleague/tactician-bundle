<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder;

use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class BusBuilder
{
    /**
     * @var string
     */
    private $busId;

    /**
     * @var string[]
     */
    private $middlewareIds;

    /** @var string */
    private $commandToHandlerMapping;

    public function __construct(string $busId, string $commandToHandlerMapping, array $middlewareIds)
    {
        $this->busId                   = $busId;
        $this->commandToHandlerMapping = $commandToHandlerMapping;
        $this->middlewareIds           = $middlewareIds;
    }

    public function id() : string
    {
        return $this->busId;
    }

    public function serviceId() : string
    {
        return "tactician.commandbus.$this->busId";
    }

    public function commandHandlerMiddlewareId() : string
    {
        return "tactician.commandbus.{$this->busId}.middleware.command_handler";
    }

    public function registerInContainer(ContainerBuilder $container) : void
    {
        $container->setDefinition(
            $this->commandHandlerMiddlewareId(),
            new Definition(
                CommandHandlerMiddleware::class,
                [
                    new Reference('service_container'),
                    new Reference($this->commandToHandlerMapping),
                ]
            )
        );

        $container->setDefinition(
            $this->serviceId(),
            new Definition(
                CommandBus::class,
                array_map(
                    static function (string $id) {
                        return new Reference($id);
                    },
                    $this->middlewareIds
                )
            )
        )->setPublic(true);
    }
}
