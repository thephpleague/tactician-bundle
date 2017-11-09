<?php

namespace League\Tactician\Bundle\Tests\Integration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

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

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function testHandleCommandWithInvalidMiddleware()
    {
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

    /**
     * @expectedException \League\Tactician\Bundle\DependencyInjection\InvalidCommandBusId
     * @expectedExceptionMessage Could not find a command bus with id 'other'. Valid buses are: default
     */
    public function testHandlerOnUnknownBus()
    {
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

    /**
     * @expectedException \League\Tactician\Exception\MissingHandlerException
     */
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
        $this->handleCommand('default', \League\Tactician\Bundle\Tests\EchoText::class, ['Welcome']);
    }
}
