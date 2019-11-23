<?php

declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

abstract class IntegrationTest extends KernelTestCase
{
    /**
     * @var Kernel
     */
    protected static $kernel;

    /**
     * @var Filesystem
     */
    private $filesystem;

    protected static function createKernel(array $options = array())
    {
        require_once __DIR__.'/../testapp/AppKernel.php';

        return new \AppKernel('test', true);
    }

    protected function setUp() : void
    {
        static::$kernel = static::createKernel();
        $this->filesystem = new Filesystem();

        $cacheDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tactician-bundle'.DIRECTORY_SEPARATOR.uniqid("tactician-bundle", true);

        $this->filesystem->mkdir($cacheDir);
        static::$kernel->defineCacheDir($cacheDir);
    }

    protected function tearDown() : void
    {
        $this->filesystem->remove(
            static::$kernel->getCacheDir()
        );
    }

    protected function givenConfig($namespace, $config)
    {
        static::$kernel->loadConfig($namespace, Yaml::parse((string) $config));
    }

    protected function registerService($serviceId, $className, array $tags)
    {
        static::$kernel->addServiceToRegister($serviceId, $className, $tags);
    }

    protected function handleCommand($busId, $commandClass, array $args = [])
    {
        $class = new \ReflectionClass($commandClass);
        $command = $class->newInstanceArgs($args);

        static::$kernel->boot();
        static::$kernel->getContainer()->get('tactician.commandbus.'.$busId)->handle($command);
    }
}
