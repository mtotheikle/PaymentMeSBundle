<?php

namespace ImmersiveLabs\BillingBundle\Service;

use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;

class MeSPlugin extends AbstractPlugin
{
    public function processes($name)
    {
        return 'MeS' === $name;
    }
}