<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgCredit extends TpgTransaction
{
    function TpgCredit($profileId, $profileKey)
    {
        $this->TpgTransaction($profileId, $profileKey);
        $this->TranType = "C";
    }

    function setStoredData($cardId, $amount)
    {
        $this->RequestFields['card_id'] = $cardId;
        $this->RequestFields['transaction_amount'] = $amount;
    }
}
