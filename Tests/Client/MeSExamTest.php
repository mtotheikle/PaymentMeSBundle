<?php

namespace ImmersiveLabs\PaymentMeSBundle\Tests\Client;

use ImmersiveLabs\PaymentMeSBundle\Tests\BaseTestCase;
use ImmersiveLabs\PaymentMeSBundle\Client\MeSClient;
use ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

/**
 * @group exam
 */
class MeSExamTest extends BaseTestCase
{
    /** @var MeSClient */
    protected $mesClient;

    public function setUp()
    {
        parent::setUp();
    }

    public function testExam()
    {
        $cardNumbers = array(
            '4012301230123010',
            '5123012301230120',
            '349999999999991',
            '6011011231231235'
        );

        $profileId = $this->container->getParameter('pg_profile_id');
        $profileKey = $this->container->getParameter('pg_profile_key');

        foreach ($cardNumbers as $cardNumber) {
            $request = new Trident\TpgSale($profileId, $profileKey);

            $invoice = uniqid();
            $request->RequestFields = array(
                'card_number'               => $cardNumber,
                'card_exp_date'             => '072017',
                'transaction_amount'        => 0.03,
                'cvv2'                      => '123',
                'cardholder_street_address' => '123',
                'cardholder_zipcode'        => '55555',
                'invoice_number'            => $invoice
            );

            $request->execute();

            $refundRequest = new Trident\TpgRefund($profileId, $profileKey, $request->ResponseFields['transaction_id']);
            $refundRequest->execute();

            echo sprintf("Card number : %s \n", $cardNumber);
            echo sprintf("Invoice : %s \n", $invoice);
            echo sprintf("Sale Transaction Id : %s \n", $request->ResponseFields['transaction_id']);
            echo sprintf("Refund Transaction Id : %s \n\n\n", $refundRequest->ResponseFields['transaction_id']);
        }
    }
}
