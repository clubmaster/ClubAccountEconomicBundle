<?php

namespace Club\Account\EconomicBundle\DependencyInjection;

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
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('club_account_e_conomic');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
          ->children()
            ->scalarNode('enabled')->isRequired()->end()
            ->scalarNode('agreement')->defaultValue(null)->end()
            ->scalarNode('username')->defaultValue(null)->end()
            ->scalarNode('password')->defaultValue(null)->end()
            ->scalarNode('economic_url')->defaultValue('https://api.e-conomic.com/secure/api1/EconomicWebservice.asmx?WSDL')->end()
          ->end();

        return $treeBuilder;
    }
}
