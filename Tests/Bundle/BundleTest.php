<?php

namespace League\Tactician\Bundle\Tests\Bundle;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * To ensure cache is isolated from each test.
 *
 * @runTestsInSeparateProcesses
 */
class BundleTest extends KernelTestCase
{
    protected function setUp()
    {
        static::$kernel = static::createKernel();
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tactician-bundle'.DIRECTORY_SEPARATOR.md5(microtime(true) * rand(0, 10000));
        mkdir($dir, 0777, true);
        static::$kernel->defineCacheDir($dir);
    }

    protected static function createKernel(array $options = array())
    {
        require_once __DIR__.'/../testapp/AppKernel.php';

        return new \AppKernel('test', true);
    }

    public function testHandleCommandOnDefaultBus()
    {
        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.command_handler
EOF
        );
        $this->registerService('tactician.test.handler', \League\Tactician\Bundle\Tests\EchoTextHandler::class, [
            ['name' => 'tactician.handler', 'command' => 'League\Tactician\Bundle\Tests\EchoText'],
        ]);

        $this->expectOutputString('Hello world');
        $this->handleCommand('default', \League\Tactician\Bundle\Tests\EchoText::class, ['Hello world']);
    }

    /**
     * @expectedException \League\Tactician\Bundle\DependencyInjection\Compiler\UnknownMiddlewareException
     */
    public function testHandleCommandWithInvalidMiddleware()
    {
        $this->givenConfig('tactician', <<<'EOF'
commandbus:
    default:
        middleware:
            - tactician.middleware.validator
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
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid bus id "other". Valid buses are: default
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

    protected function givenConfig($namespace, $config)
    {
        static::$kernel->loadConfig($namespace, Yaml::parse((string) $config));
    }

    protected function registerService($serviceId, $className, array $tags)
    {
        static::$kernel->addServiceToRegister($serviceId, $className, $tags);
    }

    protected function handleCommand($busId, $commandClass, array $args)
    {
        $class = new \ReflectionClass($commandClass);
        $command = $class->newInstanceArgs($args);

        static::$kernel->boot();
        static::$kernel->getContainer()->get('tactician.commandbus.'.$busId)->handle($command);
    }
}
