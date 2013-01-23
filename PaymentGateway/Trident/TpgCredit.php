<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgCredit extends TpgTransaction
{
    function __construct($profileId, $profileKey)
    {
        parent::__construct($profileId, $profileKey);
        $this->TranType = "C";
    }

    function setStoredData($cardId, $amount)
    {
        $this->RequestFields['card_id'] = $cardId;
        $this->RequestFields['transaction_amount'] = $amount;
    }
}
