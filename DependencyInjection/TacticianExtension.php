<?php namespace Xtrasmal\TacticianBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class TacticianExtension extends ConfigurableExtension
{
    /**
     * Configures the passed container according to the merged configuration.
     *
     * @param array $mergedConfig
     * @param ContainerBuilder $container
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config/services'));
        $loader->load('services.yml');

        $middlewares = array_map(
            function ($middlewareServiceId) {
                return new Reference($middlewareServiceId);
            },
            $mergedConfig['middlewares']
        );

        // Load the commandbus service so we can bootstrap config as arguments for the factory method
        $commandbus = $container->getDefinition('tactician.commandbus');
        $commandbus->setArguments([$middlewares]);
    }

    public function getAlias()
    {
        return 'tactician';
    }
}
