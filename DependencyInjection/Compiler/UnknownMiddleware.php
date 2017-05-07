<?php

namespace League\Tactician\Bundle\DependencyInjection\Compiler;

use League\Tactician\Bundle\Middleware;

class UnknownMiddleware extends \RuntimeException
{
    public static function withId($serviceId)
    {
        return new static(
            trim(sprintf('Unknown middleware with service id "%s". %s', $serviceId, static::completeHelpMessage($serviceId)))
        );
    }

    private static function completeHelpMessage($serviceId)
    {
        $helpMessages = [
            Middleware\ValidatorMiddleware::SERVICE_ID => 'You should have the symfony validator service enabled to use this middleware.',
            Middleware\SecurityMiddleware::SERVICE_ID => 'You should have the symfony security service enabled to use this middleware.',
        ];

        if (array_key_exists($serviceId, $helpMessages)) {
            return $helpMessages[$serviceId];
        }
    }
}
