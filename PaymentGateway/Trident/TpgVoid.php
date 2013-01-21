<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgVoid extends TpgTransaction
{
    function TpgVoid($profileId, $profileKey, $tranId)
    {
        $this->TpgTransaction($profileId, $profileKey);
        $this->RequestFields['transaction_id'] = $tranId;
        $this->TranType = "V";
    }

    function setStoredData($cardId, $amount)
    {
        $this->RequestFields['card_id'] = $cardId;
        $this->RequestFields['transaction_amount'] = $amount;
    }
}
