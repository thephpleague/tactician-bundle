<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\DependencyInjection\HandlerMapping;

final class TestMappingData
{
    public static function example()
    {
        return [
            'default' => [
                FakeCommand::class => 'fake.handler'
            ],
            'foo' => [
                FakeCommand::class => 'fake.handler',
                OtherFakeCommand::class => 'other_fake.handler'
            ],
            'bar' => [
                FakeCommand::class => 'fake.handler'
            ]
        ];
    }
}
