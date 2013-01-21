<?php

namespace ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class TpgPreAuth extends TpgTransaction
{
    function TpgPreAuth($profileId = '', $profileKey = '')
    {
        $this->TpgTransaction($profileId, $profileKey);
        $this->TranType = "P"; // pre-auth
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
