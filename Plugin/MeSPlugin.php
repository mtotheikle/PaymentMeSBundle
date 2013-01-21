<?php

namespace ImmersiveLabs\PaymentMeSBundle\Plugin;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;

class MeSPlugin extends AbstractPlugin
{
    protected $pgProfileId;
    protected $pgProfileKey;
    protected $pgHost;

    /**
     * @param string $pgProfileId
     * @param string $pgProfileKey
     * @param string $pgHost
     */
    public function __construct($pgProfileId, $pgProfileKey, $pgHost)
    {
        $this->pgProfileId = $pgProfileId;
        $this->pgProfileKey = $pgProfileKey;
        $this->pgHost = $pgHost;
    }

    public function processes($name)
    {
        return 'MeS' === $name;
    }
}