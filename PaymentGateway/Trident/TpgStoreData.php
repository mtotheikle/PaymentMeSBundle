<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgStoreData extends TpgTransaction
{
    function TpgStoreData($profileId, $profileKey)
    {
        $this->TpgTransaction($profileId, $profileKey);
        $this->TranType = "T";
    }
}
