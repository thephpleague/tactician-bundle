<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection;

final class InvalidCommandBusId extends \Exception
{
    public static function ofName(string $expectedId, array $validIds)
    {
        return new static(
            "Could not find a command bus with id '$expectedId'. Valid buses are: " . implode(', ', $validIds)
        );
    }
}