<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgSettle extends TpgTransaction
{
    function __construct($profileId, $profileKey, $tranId, $settleAmount = 0)
    {
        parent::__construct($profileId, $profileKey);
        $this->RequestFields['transaction_id'] = $tranId;
        $this->RequestFields['transaction_amount'] = $settleAmount;
        $this->TranType = "S";
    }

    function setSettlementAmount($settleAmount)
    {
        $this->RequestFields['transaction_amount'] = $settleAmount;
    }
}
