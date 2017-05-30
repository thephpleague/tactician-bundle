<?php
declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\Integration;

use League\Tactician\Bundle\DependencyInjection\Compiler\UnknownMiddlewareException;

/**
 * @runTestsInSeparateProcesses
 */
final class ValidatorTest extends IntegrationTest
{
    public function testCanBootKernelWhenOptionalComponentMiddlewareIsEnabled()
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
EOF
        );
        static::$kernel->boot();
    }

    public function testCanNotBootKernelWhenOptionalComponentMiddlewareIsDisabled()
    {
        $this->expectException(UnknownMiddlewareException::class);

        $this->givenConfig('framework', <<<'EOF'
validation:
    enabled: false
EOF
        );

        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.validator
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
}
