<?php
namespace Xtrasmal\TacticianBundle\Handler;

use League\Tactician\Command;
use League\Tactician\Exception\MissingHandlerException;
use League\Tactician\Handler\Locator\HandlerLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lazily loads Command Handlers from the Symfony2 DI container
 */
class ContainerBasedHandlerLocator implements HandlerLocator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $commandToServiceId = [];

    /**
     * @param ContainerInterface $container
     * @param $commandToServiceIdMapping
     */
    public function __construct(ContainerInterface $container, $commandToServiceIdMapping)
    {
        $this->container = $container;
        $this->commandToServiceId = $commandToServiceIdMapping;
    }

    /**
     * Retrieves the handler for a specified command
     *
     * @param Command $command
     * @return mixed
     */
    public function getHandlerForCommand(Command $command)
    {
        $commandClass = get_class($command);
        if (!isset($this->commandToServiceId[$commandClass])) {
            throw MissingHandlerException::forCommand($command);
        }

        return $this->container->get($this->commandToServiceId[$commandClass]);
    }
}
