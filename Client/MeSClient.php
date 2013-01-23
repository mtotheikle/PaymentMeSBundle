<?php

namespace ImmersiveLabs\PaymentMeSBundle\Client;

class MeSClient
{
    protected $profileId;
    protected $profileKey;
    protected $apiUrl;

    public function __construct($auth)
    {
        $this->profileId = $auth['profileId'];
        $this->profileKey = $auth['profileKey'];
        $this->apiUrl = $auth['apiUrl'];
    }

    public function postSale($cardNumber, \DateTime $cardExpDate, $amount)
    {
        $ch = curl_init($this->getApiUrl());
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(array(
                'profile_id' => $this->getProfileId(),
                'profile_key' => $this->getProfileKey(),
                'card_number' => $cardNumber,
                'card_exp_date' => $cardExpDate->format('my'),
                'transaction_amount' => $amount,
                'transaction_type' => 'D',
                'invoice_number' => $this->generateInvoiceNumber(),
            ))
        ));

        $res = curl_exec($ch);
        curl_close($ch);

        parse_str($res, $resultingArray);
        ladybug_dump($resultingArray);
    }

    private function generateInvoiceNumber($length = 10)
    {
        $base = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
        shuffle($base);

        $invoiceNumber = '';

        while(strlen($invoiceNumber) < $length) {
            $invoiceNumber .= $base[array_rand($base)];
            shuffle($base);
        }

        return $invoiceNumber;
    }
}
