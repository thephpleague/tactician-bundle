<?php

declare(strict_types=1);

namespace League\Tactician\Bundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

abstract class IntegrationTest extends KernelTestCase
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    protected static function createKernel(array $options = array()): KernelInterface
    {
        require_once __DIR__.'/../testapp/AppKernel.php';

        return new \AppKernel('test', true);
    }

    protected function setUp(): void
    {
        static::$kernel = static::createKernel();
        $this->filesystem = new Filesystem();

        // Default config so unrelated tests don't break
        $securityConfig = [
            'access_denied_url' => '/',
            'firewalls' => [
                'main' => ['http_basic' => []],
            ],
        ];

        // Symfony 5.3-7.3 requires enable_authenticator_manager
        // In Symfony 7.4+, this option was removed (authenticator manager is always enabled)
        if (interface_exists('Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface')) {
            // Use Kernel version to check if we're on Symfony < 7.4
            if (defined('Symfony\Component\HttpKernel\Kernel::VERSION')) {
                $version = \Symfony\Component\HttpKernel\Kernel::VERSION;
                if (version_compare($version, '7.4', '<')) {
                    $securityConfig['enable_authenticator_manager'] = true;
                }
            } else {
                // Fallback for older Symfony versions without VERSION constant
                $securityConfig['enable_authenticator_manager'] = true;
            }
        }

        static::$kernel->loadConfig('security', $securityConfig);

        $cacheDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tactician-bundle'.DIRECTORY_SEPARATOR.uniqid("tactician-bundle", true);

        $this->filesystem->mkdir($cacheDir);
        static::$kernel->defineCacheDir($cacheDir);
    }

    protected function tearDown(): void
    {
        $cacheDir = static::$kernel ? static::$kernel->getCacheDir() : null;

        // Manually shutdown and nullify kernel to prevent parent::tearDown() from trying to boot a misconfigured kernel
        if (static::$kernel !== null) {
            static::$kernel->shutdown();
            static::$kernel = null;
        }

        parent::tearDown();

        if ($cacheDir && $this->filesystem->exists($cacheDir)) {
            $this->filesystem->remove($cacheDir);
        }
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
