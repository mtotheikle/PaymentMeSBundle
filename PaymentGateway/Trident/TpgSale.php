<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgSale extends TpgTransaction
{
    function __construct($profileId, $profileKey)
    {
        parent::__construct($profileId, $profileKey);
        $this->TranType = "D";
    }

    function setStoredData($cardId, $amount)
    {
        $this->RequestFields['card_id'] = $cardId;
        $this->RequestFields['transaction_amount'] = $amount;
    }

    function setFXData($amt, $rid, $curr)
    {
        $this->RequestFields['fx_amount'] = $amt;
        $this->RequestFields['fx_rate_id'] = $rid;
        $this->RequestFields['currency_code'] = $curr;
    }

    function setEcommInd($ind)
    {
        $this->RequestFields['moto_ecommerce_ind'] = $ind;
    }
}