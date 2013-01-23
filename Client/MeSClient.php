<?php

namespace ImmersiveLabs\PaymentMeSBundle\Client;

use ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class MeSClient
{
    protected $profileId;
    protected $profileKey;
    protected $apiUrl;

    public function __construct($auth)
    {
        list($this->profileId, $this->profileKey, $this->apiUrl) = $auth;
    }

    public function postSale($cardNumber, $expirationMonth, $expirationYear, $amount)
    {
        $sale = new Trident\TpgSale($this->profileId, $this->profileKey);

        $sale->setTransactionData($cardNumber, $expirationMonth . $expirationYear, $amount);
        $sale->execute();

        if (!$sale->isApproved()) {
            return false;
        }


        return $sale->ResponseFields['auth_code'];
    }
}
