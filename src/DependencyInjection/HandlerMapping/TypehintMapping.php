<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection\HandlerMapping;

use Symfony\Component\DependencyInjection\Definition;

final class TypehintMapping extends TagBasedMapping
{
    public function isSupported(Definition $definition, array $tagAttributes): bool
    {
        return isset($tagAttributes['auto']) && $tagAttributes['auto'] === true;
    }

    public function findCommandsForService(Definition $definition, array $tagAttributes): array
    {
        return []; // TODO: reflection magic!
    }
}
