<?php

namespace ImmersiveLabs\PaymentMeSBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BaseTestCase extends WebTestCase
{
    protected $webClient;

    /** @var \Symfony\Component\DependencyInjection\Container */
    protected $container;

    /** @var \ImmersiveLabs\PaymentMeSBundle\Client\MeSClient */
    protected $mesClient;

    public function setUp()
    {
        $this->webClient = static::createClient();
        $this->container = $this->webClient->getContainer();

        $this->mesClient = $this->container->get('mes_client');
    }
}
