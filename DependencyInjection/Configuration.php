<?php

namespace ImmersiveLabs\PaymentMeSBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle Configuration
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        return $treeBuilder
            ->root('payment_me_s')
                ->children()
                    ->scalarNode('pg_profile_id')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('pg_profile_key')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('pg_host')
                        ->defaultValue('https://cert.merchante-solutions.com/mes-api/tridentApi')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end();
    }
}