<?php namespace Xtrasmal\TacticianBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{

    /**
     * Create a rootnode tree for configuration that can be injected into the DI container
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tactician');
        $rootNode
            ->children()
                ->scalarNode('default_bus')
                    ->defaultValue('default')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('commandbus')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('middleware')
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue(['default' => ['middleware' => ['tactician.middleware.command_handler']]])
                ->end()
            ->end();
        return $treeBuilder;

    }
}
