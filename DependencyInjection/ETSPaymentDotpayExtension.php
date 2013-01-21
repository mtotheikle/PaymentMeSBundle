<?php

namespace ETS\Payment\DotpayBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Bundle Extension class
 *
 */
class ETSPaymentDotpayExtension extends Extension
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

        $container->setParameter('payment.dotpay.direct.id', $config['direct']['id']);
        $container->setParameter('payment.dotpay.direct.pin', $config['direct']['pin']);
        $container->setParameter('payment.dotpay.direct.url', $config['direct']['url']);
        $container->setParameter('payment.dotpay.direct.type', $config['direct']['type']);
        $container->setParameter('payment.dotpay.direct.return_url', $config['direct']['return_url']);
    }
}