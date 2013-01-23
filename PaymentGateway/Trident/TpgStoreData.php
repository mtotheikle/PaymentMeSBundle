<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgStoreData extends TpgTransaction
{
    function __construct($profileId, $profileKey)
    {
        parent::__construct($profileId, $profileKey);
        $this->TranType = "T";
    }
}
