<?php

namespace League\Tactician\Bundle\DependencyInjection\Compiler;

class UnknownMiddlewareException extends \RuntimeException
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
            ValidatorMiddlewarePass::SERVICE_ID => 'You should have the symfony validator service enabled to use this middleware.',
            SecurityMiddlewarePass::SERVICE_ID => 'You should have the symfony security service enabled to use this middleware.',
        ];

        if (array_key_exists($serviceId, $helpMessages)) {
            return $helpMessages[$serviceId];
        }
    }
}
