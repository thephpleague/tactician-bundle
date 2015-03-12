<?php namespace Xtrasmal\TacticianBundle\Setup;

use League\Tactician\CommandBus;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Handler\MethodNameInflector\HandleInflector;
use Xtrasmal\TacticianBundle\Handler\CommandHandlerMiddleware;
use League\Tactician\Plugins\LockingMiddleware;


//  Tagged services for every middleware that has been added. The middleware order is important
//  dus fix dat dat dat dat dat dat- kablam.


/**
 * Builds a working command bus
 */
class Setup
{
    /**
     * Creates a default CommandBus that you can get started with.
     *
     * @param array $commandToHandlerMap
     * @param array $middlewares
     *
     * @return CommandBus
     */
    public static function create($commandToHandlerMap, $middlewares = [])
    {

        var_dump($middlewares);
        $handlerMiddleware = new CommandHandlerMiddleware(
            new InMemoryLocator($commandToHandlerMap),
            new HandleInflector()
        );

        $lockingMiddleware = new LockingMiddleware();

        return new CommandBus([$lockingMiddleware, $handlerMiddleware]);

    }

}
