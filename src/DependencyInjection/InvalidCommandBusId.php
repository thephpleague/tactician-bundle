<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection;

use Exception;
use function implode;
use function sprintf;

final class InvalidCommandBusId extends Exception
{
    /**
     * @param string[] $validIds
     */
    public static function ofName(string $expectedId, array $validIds) : self
    {
        return new static(
            sprintf("Could not find a command bus with id '%s'. Valid buses are: %s", $expectedId, implode(', ', $validIds))
        );
    }
}
