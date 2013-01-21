<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgRemoveData extends TpgTransaction
{
    function TpgRemoveData($profileId, $profileKey, $cardId)
    {
        $this->TpgTransaction($profileId, $profileKey);
        $this->RequestFields['card_id'] = $cardId;
        $this->TranType = "X";
    }
}
