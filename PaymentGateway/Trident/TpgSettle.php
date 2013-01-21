<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgSettle extends TpgTransaction
{
    function TpgSettle($profileId, $profileKey, $tranId, $settleAmount = 0)
    {
        $this->TpgTransaction($profileId, $profileKey);
        $this->RequestFields['transaction_id'] = $tranId;
        $this->RequestFields['transaction_amount'] = $settleAmount;
        $this->TranType = "S";
    }

    function setSettlementAmount($settleAmount)
    {
        $this->RequestFields['transaction_amount'] = $settleAmount;
    }
}
