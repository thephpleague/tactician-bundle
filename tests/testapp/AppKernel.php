<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    private $config = [];

    private $services = [];

    public function registerBundles()
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new League\Tactician\Bundle\TacticianBundle(),
        ];
    }

    public function defineCacheDir($dir)
    {
        $this->cacheDir = $dir;
    }

    public function loadConfig($namespace, array $tacticianConfig)
    {
        $this->config[$namespace] = $tacticianConfig;
    }

    public function addServiceToRegister($serviceId, $className, array $tags)
    {
        $this->services[$serviceId] = [
            'className' => $className,
            'tags' => $tags,
        ];
    }

    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $frameworkConfig = ['secret' => 'S0ME_SECRET'];
        if (array_key_exists('framework', $this->config)) {
            $this->config['framework'] = array_merge($frameworkConfig, $this->config['framework']);
        } else {
            $this->config['framework'] = $frameworkConfig;
        }

        foreach ($this->config as $namespace => $config) {
            $c->loadFromExtension($namespace, $config);
        }

        foreach ($this->services as $serviceId => $definition) {
            $service = $c->register($serviceId, $definition['className']);
            foreach ($definition['tags'] as $tag) {
                $tagName = $tag['name'];
                unset($tag['name']);
                $service->addTag($tagName, $tag);
            }
        }
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }
}
