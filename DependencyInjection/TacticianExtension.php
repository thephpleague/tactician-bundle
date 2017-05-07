<?php namespace League\Tactician\Bundle\DependencyInjection;

use League\Tactician\Bundle\Security\Voter\HandleCommandVoter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
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

        $this->configureCommandBuses($mergedConfig, $container);
        $this->configureSecurity($mergedConfig, $container);
    }

    public function getAlias()
    {
        return 'tactician';
    }

    /**
     * @param array $mergedConfig
     * @param ContainerBuilder $container
     */
    private function configureCommandBuses(array $mergedConfig, ContainerBuilder $container)
    {
        foreach ($mergedConfig['commandbus'] as $commandBusName => $commandBusConfig) {
            $middlewares = array_map(
                function ($middlewareServiceId) {
                    return new Reference($middlewareServiceId);
                },
                $commandBusConfig['middleware']
            );

            $serviceName = 'tactician.commandbus.' . $commandBusName;
            $definition = new Definition($container->getParameter('tactician.commandbus.class'), [$middlewares]);
            $container->setDefinition($serviceName, $definition);

            if ($commandBusName === $mergedConfig['default_bus']) {
                $container->setAlias('tactician.commandbus', $serviceName);
            }
            $container->setParameter(
                sprintf('tactician.method_inflector.%s', $commandBusName),
                array_key_exists('method_inflector', $commandBusConfig)
                    ? $commandBusConfig['method_inflector']
                    : $mergedConfig['method_inflector']
            );
        }
        $container->setParameter('tactician.commandbus.default', $mergedConfig['default_bus']);
        $container->setParameter('tactician.commandbus.ids', array_keys($mergedConfig['commandbus']));
    }

    /**
     * Configure the security voter if the security middleware is loaded.
     *
     * @param array $mergedConfig
     * @param ContainerBuilder $container
     */
    private function configureSecurity(array $mergedConfig, ContainerBuilder $container)
    {
        foreach ($mergedConfig['commandbus'] as $commandBusConfig) {
            if (in_array('tactician.middleware.security', $commandBusConfig['middleware'])) {
                return $this->configureCommandSecurityVoter($mergedConfig, $container);
            }
        }
    }

    /**
     * Configure the security voter.
     *
     * @param array $mergedConfig
     * @param ContainerBuilder $container
     */
    private function configureCommandSecurityVoter(array $mergedConfig, ContainerBuilder $container)
    {
        if (!$container->has('tactician.middleware.security_voter')) {
            $definition = new Definition(
                HandleCommandVoter::class,
                [
                    new Reference('security.access.decision_manager'),
                    $mergedConfig['security']
                ]
            );
            $definition->addTag('security.voter');
            $container->setDefinition('tactician.middleware.security_voter', $definition);
        }
    }
}
