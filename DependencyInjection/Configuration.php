<?php

namespace ETS\Payment\DotpayBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle Configuration
 *
 * @author ETSGlobal <e4-devteam@etsglobal.org>
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
            ->root('payment_mes_bundle')
                ->children()
                    ->arrayNode('direct')
                        ->children()
                            ->scalarNode('id')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('pin')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('url')
                                ->defaultValue('https://ssl.dotpay.pl/')
                                ->cannotBeEmpty()
                                ->validate()
                                    ->ifNotInArray(array('https://ssl.dotpay.pl/', 'https://ssl.dotpay.eu/'))
                                    ->thenInvalid('Invalid dotpay url "%s"')
                                ->end()
                            ->end()
                            ->scalarNode('type')
                                ->defaultValue(2)
                                ->validate()
                                    ->ifNotInArray(array(0, 1, 2, 3))
                                    ->thenInvalid('Invalid type "%s"')
                                ->end()
                            ->end()
                            ->scalarNode('return_url')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}