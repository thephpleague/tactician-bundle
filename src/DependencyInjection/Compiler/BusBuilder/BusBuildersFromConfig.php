<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder;

final class BusBuildersFromConfig
{
    public const DEFAULT_BUS_ID                  = 'default';
    public const DEFAULT_COMMAND_HANDLER_MAPPING = 'tactician.handler.command_handler_mapping.map_by_naming_convention';

    public static function convert(array $config) : BusBuilders
    {
        $defaultCommandHandlerMapping = $config['command_handler_mapping'] ?? self::DEFAULT_COMMAND_HANDLER_MAPPING;

        $builders = [];
        foreach ($config['commandbus'] ?? [] as $busId => $busConfig) {
            $builders[] = new BusBuilder(
                $busId,
                $busConfig['command_handler_mapping'] ?? $defaultCommandHandlerMapping,
                $busConfig['middleware']
            );
        }

        return new BusBuilders($builders, $config['default_bus'] ?? self::DEFAULT_BUS_ID);
    }
}
