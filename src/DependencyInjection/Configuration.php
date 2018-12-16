<?php

namespace League\Tactician\Bundle\DependencyInjection;

use League\Tactician\Bundle\DependencyInjection\Compiler\BusBuilder\BusBuildersFromConfig;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Create a rootnode tree for configuration that can be injected into the DI container.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('tactician');

        if (\method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('tactician');
        }

        $rootNode
            ->children()
                ->arrayNode('commandbus')
                    ->defaultValue(['default' => ['middleware' => ['tactician.middleware.command_handler']]])
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('middleware')
                                ->requiresAtLeastOneElement()
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                                ->validate()
                                    ->ifTrue(function ($config) {
                                        $isPresent = in_array('tactician.middleware.command_handler', $config);
                                        $isLast = end($config) == 'tactician.middleware.command_handler';

                                        return $isPresent && !$isLast;
                                    })
                                    ->thenInvalid(
                                        '"tactician.middleware.command_handler" should be the last middleware loaded '.
                                        'when it is used.'
                                    )
                                ->end()
                            ->end()
                            ->scalarNode('method_inflector')->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_bus')
                    ->defaultValue(BusBuildersFromConfig::DEFAULT_BUS_ID)
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('method_inflector')
                    ->defaultValue(BusBuildersFromConfig::DEFAULT_METHOD_INFLECTOR)
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('security')
                    ->defaultValue([])
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
                ->scalarNode('logger_formatter')
                    ->defaultValue('tactician.logger.class_properties_formatter')
                    ->cannotBeEmpty()
                ->end()
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return is_array($config) &&
                        array_key_exists('default_bus', $config) &&
                        array_key_exists('commandbus', $config)
                    ;
                })
                    ->then(function ($config) {
                        $busNames = [];
                        foreach ($config['commandbus'] as $busName => $busConfig) {
                            $busNames[] = $busName;
                        }

                        if (!in_array($config['default_bus'], $busNames)) {
                            throw new InvalidConfigurationException(
                                sprintf(
                                    'The default_bus "%s" was not defined as a command bus. Valid option(s): %s',
                                    $config['default_bus'],
                                    implode(', ', $busNames)
                                )
                            );
                        }

                        return $config;
                    })
            ->end()
        ;

        return $treeBuilder;
    }
}
