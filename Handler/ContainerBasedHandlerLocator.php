<?php
namespace League\Tactician\Bundle\Handler;

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
     * @param string $commandName
     * @return mixed
     */
    public function getHandlerForCommand($commandName)
    {
        if (!isset($this->commandToServiceId[$commandName])) {
            throw MissingHandlerException::forCommand($commandName);
        }

        return $this->container->get($this->commandToServiceId[$commandName]);
    }
}
