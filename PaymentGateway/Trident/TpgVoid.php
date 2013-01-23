<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgVoid extends TpgTransaction
{
    function __construct($profileId, $profileKey, $tranId)
    {
        parent::__construct($profileId, $profileKey);
        $this->RequestFields['transaction_id'] = $tranId;
        $this->TranType = "V";
    }

    function setStoredData($cardId, $amount)
    {
        $this->RequestFields['card_id'] = $cardId;
        $this->RequestFields['transaction_amount'] = $amount;
    }
}
