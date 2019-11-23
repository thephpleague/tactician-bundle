<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection;

use Exception;
use function sprintf;

final class DuplicatedCommandBusId extends Exception
{
    public static function withId(string $id) : self
    {
        return new static(
            sprintf("There are multiple command buses with the id '%s'. All bus ids must be unique.", $id)
        );
    }
}
