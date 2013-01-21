<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgOffline extends TpgTransaction
{
    function TpgOffline($profileId, $profileKey, $authCode)
    {
        $this->TpgTransaction($profileId, $profileKey);
        $this->RequestFields['auth_code'] = $authCode;
        $this->TranType = "O";
    }

    function setStoredData($cardId, $amount)
    {
        $this->RequestFields['card_id'] = $cardId;
        $this->RequestFields['transaction_amount'] = $amount;
    }
}
