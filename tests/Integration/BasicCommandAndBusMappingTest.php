<?php

namespace League\Tactician\Bundle\Tests\Integration;

use League\Tactician\Bundle\DependencyInjection\InvalidCommandBusId;
use League\Tactician\Exception\MissingHandlerException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @runTestsInSeparateProcesses
 */
class BasicCommandAndBusMappingTest extends IntegrationTest
{
    public function testHandleCommandOnDefaultBus()
    {
        $this->registerService('tactician.test.handler', \League\Tactician\Bundle\Tests\EchoTextHandler::class, [
            ['name' => 'tactician.handler', 'command' => 'League\Tactician\Bundle\Tests\EchoText'],
        ]);

        $this->expectOutputString('Hello world');
        $this->handleCommand('default', \League\Tactician\Bundle\Tests\EchoText::class, ['Hello world']);
    }

    public function testHandleCommandWithInvalidMiddleware()
    {
        $this->expectException(ServiceNotFoundException::class);

        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.whatever
            - tactician.middleware.command_handler
EOF
        );
        static::$kernel->boot();
    }

    public function testHandleCommandOnMiddlewareWithDependencies()
    {
        $this->givenConfig('framework', <<<'EOF'
validation:
    enabled: true
EOF
        );
        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.validator
            - tactician.middleware.command_handler
EOF
        );
        $this->registerService('tactician.test.handler', \League\Tactician\Bundle\Tests\EchoTextHandler::class, [
            ['name' => 'tactician.handler', 'command' => 'League\Tactician\Bundle\Tests\EchoText'],
        ]);

        $this->expectOutputString('Hello world');
        $this->handleCommand('default', \League\Tactician\Bundle\Tests\EchoText::class, ['Hello world']);
    }

    public function testHandleCommandOnSpecificBus()
    {
        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.command_handler
    other:
        middleware:
            - tactician.commandbus.other.middleware.command_handler
EOF
        );
        $this->registerService('tactician.test.handler', \League\Tactician\Bundle\Tests\EchoTextHandler::class, [
            ['name' => 'tactician.handler', 'command' => 'League\Tactician\Bundle\Tests\EchoText', 'bus' => 'other'],
        ]);
        $this->expectOutputString('Welcome');
        $this->handleCommand('other', \League\Tactician\Bundle\Tests\EchoText::class, ['Welcome']);
    }

    public function testHandlerOnUnknownBus()
    {
        $this->expectExceptionObject(InvalidCommandBusId::ofName('other', ['default']));

        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.command_handler
EOF
        );
        $this->registerService('tactician.test.handler', \League\Tactician\Bundle\Tests\EchoTextHandler::class, [
            ['name' => 'tactician.handler', 'command' => 'League\Tactician\Bundle\Tests\EchoText', 'bus' => 'other'],
        ]);
        static::$kernel->boot();
    }

    public function testInvalidDefaultBus()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->givenConfig('tactician', <<<'EOF'
default_bus: some_bus_that_does_not_exist
commandbus:
    default:
        middleware:
            - tactician.middleware.command_handler
EOF
        );

        static::$kernel->boot();
    }

    public function testHandleCommandSpecifiedOnAnotherBus()
    {
        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.command_handler
    other:
        middleware:
            - tactician.commandbus.other.middleware.command_handler
EOF
        );
        $this->registerService('tactician.test.handler', \League\Tactician\Bundle\Tests\EchoTextHandler::class, [
            ['name' => 'tactician.handler', 'command' => 'League\Tactician\Bundle\Tests\EchoText', 'bus' => 'other'],
        ]);

        $this->expectException(MissingHandlerException::class);
        $this->handleCommand('default', \League\Tactician\Bundle\Tests\EchoText::class, ['Welcome']);
    }
}
