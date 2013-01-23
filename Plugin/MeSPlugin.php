<?php

namespace ImmersiveLabs\PaymentMeSBundle\Plugin;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\Payment;

class MeSPlugin extends AbstractPlugin
{
    /** @var \ImmersiveLabs\PaymentMeSBundle\Client\MeSClient */
    protected $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function processes($name)
    {
        return 'payment_mes' === $name;
    }

    /**
     * Captures a payment without needing to pre-authorise.
     *
     * @param FinancialTransactionInterface $transaction
     * @param bool                          $retry
     */
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $this->convertExtendedData($transaction->getExtendedData());
        /** @var \JMS\Payment\CoreBundle\Entity\Payment $payment */
        $payment = $transaction->getPayment();

        $response = $this->client->postSale($data['cardNumber'], $data['expirationMonth'], $data['expirationYear'], $payment->getApprovingAmount());

        if ($response === false) {
            return $transaction->setResponseCode(static::REASON_CODE_INVALID);
        }

        $transaction->setProcessedAmount($payment->getApprovingAmount());
        $transaction->setReferenceNumber($response);
        $transaction->setReasonCode(static::REASON_CODE_SUCCESS);
        $transaction->setResponseCode(static::RESPONSE_CODE_SUCCESS);
    }

    public function convertExtendedData(ExtendedData $extendedData)
    {
        return array(
            'cardNumber'      => $extendedData->get('cardNumber'),
            'expirationMonth' => $extendedData->get('expirationMonth'),
            'expirationYear'  => $extendedData->get('expirationYear'),
            'cvv'             => $extendedData->get('cvv')
        );
    }


    private function generateInvoiceNumber($length = 10)
    {
        $base = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
        shuffle($base);

        $invoiceNumber = '';

        while (strlen($invoiceNumber) < $length) {
            $invoiceNumber .= $base[array_rand($base)];
            shuffle($base);
        }

        return $invoiceNumber;
    }
}