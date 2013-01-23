<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgRemoveData extends TpgTransaction
{
    function __construct($profileId, $profileKey, $cardId)
    {
        parent::__construct($profileId, $profileKey);
        $this->RequestFields['card_id'] = $cardId;
        $this->TranType = "X";
    }
}
