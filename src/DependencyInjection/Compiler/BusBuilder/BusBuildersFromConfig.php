<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder;

final class BusBuildersFromConfig
{
    const DEFAULT_METHOD_INFLECTOR = 'tactician.handler.method_name_inflector.handle';

    const DEFAULT_BUS_ID = 'default';

    public static function convert(array $config): BusBuilders
    {
        $defaultInflector = $config['method_inflector'] ?? self::DEFAULT_METHOD_INFLECTOR;

        $builders = [];
        foreach ($config['commandbus'] ?? [] as $busId => $busConfig) {
            $builders[] = new BusBuilder(
                $busId,
                $busConfig['method_inflector'] ?? $defaultInflector,
                $busConfig['middleware']
            );
        }

        return new BusBuilders($builders, $config['default_bus'] ?? self::DEFAULT_BUS_ID);
    }
}
