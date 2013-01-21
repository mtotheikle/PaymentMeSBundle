<?php

namespace ImmersiveLabs\PaymentMeSBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Bundle Extension class
 *
 */
class PaymentMeSExtension extends Extension
{
    /**
     * @param array            $configs   The configuration
     * @param ContainerBuilder $container The container builder
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        $xmlLoader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $xmlLoader->load('services.xml');

        $container->setParameter('payment.mes.pg_profile_id', $config['pg_profile_id']);
        $container->setParameter('payment.mes.pg_profile_key', $config['pg_profile_key']);
        $container->setParameter('payment.mes.pg_host', $config['pg_host']);
    }
}