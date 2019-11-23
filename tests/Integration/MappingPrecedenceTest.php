<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\Integration;

use League\Tactician\Bundle\Tests\Fake\FakeCommand;

/**
 * @runTestsInSeparateProcesses
 */
final class MappingPrecedenceTest extends IntegrationTest
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.command_handler
EOF
        );
    }

    public function test_typehint_mapping_works_standalone()
    {
        $this->registerService(
            'tactician.test.handler',
            TypehintedHandler::class,
            [['name' => 'tactician.handler', 'typehints' => true]]
        );

        $this->expectOutputString("typehint wins");
        $this->handleCommand('default', FakeCommand::class);
    }

    public function test_FQCN_mapping_works_standalone()
    {
        $this->registerService(
            'tactician.test.handler',
            PlainHandler::class,
            [['name' => 'tactician.handler', 'command' => FakeCommand::class]]
        );

        $this->expectOutputString("plain wins");
        $this->handleCommand('default', FakeCommand::class);
    }

    public function test_FQCN_mapping_has_precedence_over_typehint_mapping()
    {
        $this->registerService(
            'tactician.test.typehinted_handler',
            TypehintedHandler::class,
            [['name' => 'tactician.handler', 'typehints' => true]]
        );

        $this->registerService(
            'tactician.test.plain_handler',
            PlainHandler::class,
            [['name' => 'tactician.handler', 'command' => FakeCommand::class]]
        );

        $this->expectOutputString("plain wins");
        $this->handleCommand('default', FakeCommand::class);
    }
}

class TypehintedHandler
{
    public function handle(FakeCommand $command)
    {
        echo 'typehint wins';
    }
}

class PlainHandler
{
    public function handle($someCommand)
    {
        echo 'plain wins';
    }
}
