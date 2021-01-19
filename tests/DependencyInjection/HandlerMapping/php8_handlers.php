<?php

namespace League\Tactician\Bundle\Tests\DependencyInjection\HandlerMapping;

class UnionTypeHandler
{
    public function handle(int|bool $foo)
    {
    }
}
