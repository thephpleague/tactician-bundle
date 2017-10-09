<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\DependencyInjection;

final class DuplicatedCommandBusId extends \Exception
{
    public static function withId(string $id)
    {
        return new static("There are multiple command buses with the id '$id'. All bus ids must be unique.");
    }
}
