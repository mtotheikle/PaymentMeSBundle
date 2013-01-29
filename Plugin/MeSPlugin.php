<?php

namespace ImmersiveLabs\PaymentMeSBundle\Plugin;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Entity\Credit;
use Vespolina\Entity\Partner\PaymentProfile;

class MeSPlugin extends AbstractPlugin
{
    /** @var \ImmersiveLabs\PaymentMeSBundle\Client\MeSClient */
    protected $client;

    const PARAMS_CARD_NUMBER = 'cardNumber';
    const PARAMS_CARD_EXPIRATION_MONTH = 'cardExpirationMonth';
    const PARAMS_CARD_EXPIRATION_YEAR = 'cardExpirationYear';
    const PARAMS_CARD_CVV = 'cardCvv';
    const PARAMS_CARD_ID = 'cardId';

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function processes($name)
    {
        return 'payment_mes' === $name;
    }

    /**
     * Refund transaction
     *
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     * @param bool $retry
     */
    public function credit(FinancialTransactionInterface $transaction, $retry)
    {
        /** @var Payment $payment  */
        $payment = $transaction->getPayment();
        $validTransaction = $this->searchForValidTransaction($payment);
        $txnId = $validTransaction->getReferenceNumber();

        $response = $this->client->postRefund($txnId);

        $transaction = $this->processResponse($transaction, 0, $response);
    }

    /**
     * Voids the authorized transaction id
     *
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     * @param bool $retry
     */
    public function reverseApproval(FinancialTransactionInterface $transaction, $retry)
    {
        /** @var Payment $payment  */
        $payment = $transaction->getPayment();
        $validTransaction = $this->searchForValidTransaction($payment);
        $txnId = $validTransaction->getReferenceNumber();

        $response = $this->client->postVoid($txnId);

        $transaction = $this->processResponse($transaction, 0, $response);
    }

    /**
     * Settle the authorized transaction id
     *
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     * @param bool $retry
     */
    public function deposit(FinancialTransactionInterface $transaction, $retry)
    {
        /** @var Payment $payment  */
        $payment = $transaction->getPayment();
        $validTransaction = $this->searchForValidTransaction($payment);
        $txnId = $validTransaction->getReferenceNumber();

        $response = $this->client->postSettle($txnId, $payment->getTargetAmount());

        $transaction = $this->processResponse($transaction, $payment->getTargetAmount(), $response);
    }

    /**
     * Authorize payment, doesn't capture yet
     *
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     * @param bool $retry
     */
    public function approve(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $this->convertExtendedData($transaction->getExtendedData());

        /** @var Payment $payment  */
        $payment = $transaction->getPayment();

        $response = $this->client->postPreAuth($data[self::PARAMS_CARD_NUMBER], $data[self::PARAMS_CARD_EXPIRATION_MONTH], $data[self::PARAMS_CARD_EXPIRATION_YEAR], $payment->getApprovingAmount());

        $transaction = $this->processResponse($transaction, $payment->getTargetAmount(), $response);
    }

    /**
     * Captures a payment without needing to pre-authorise.
     *
     * @param FinancialTransactionInterface $transaction
     * @param boolean                          $retry
     */
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $this->convertExtendedData($transaction->getExtendedData());
        /** @var \JMS\Payment\CoreBundle\Entity\Payment $payment */
        $payment = $transaction->getPayment();

        if (isset($data[self::PARAMS_CARD_ID])) {
            // then we pay using his card id
            $response = $this->client->postSaleForStoredData($data[self::PARAMS_CARD_ID], $payment->getApprovingAmount());
        } else {
            $response = $this->client->postSale($data[self::PARAMS_CARD_NUMBER], $data[self::PARAMS_CARD_EXPIRATION_MONTH], $data[self::PARAMS_CARD_EXPIRATION_YEAR], $payment->getApprovingAmount());
        }

        $transaction = $this->processResponse($transaction, $payment->getTargetAmount(), $response);
    }

    /**
     * @param \JMS\Payment\CoreBundle\Entity\ExtendedData $extendedData
     * @return array
     */
    public function convertExtendedData(ExtendedData $extendedData)
    {
        if ($extendedData->has('cardId')) {

            return array(
                self::PARAMS_CARD_ID => $extendedData->get('cardId')
            );
        }

        return array(
            self::PARAMS_CARD_NUMBER => $extendedData->get('cardNumber'),
            self::PARAMS_CARD_EXPIRATION_MONTH => $extendedData->get('expirationMonth'),
            self::PARAMS_CARD_EXPIRATION_YEAR  => $extendedData->get('expirationYear'),
            self::PARAMS_CARD_CVV => $extendedData->get('cvv')
        );
    }

    /**
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     * @param $amount
     * @param $response
     * @return \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface
     */
    private function processResponse(FinancialTransactionInterface $transaction, $amount, $response)
    {
        if ($response === false) {
            return $transaction->setResponseCode(static::REASON_CODE_INVALID);
        }

        $transaction->setProcessedAmount($amount);
        $transaction->setReferenceNumber($response);
        $transaction->setReasonCode(static::REASON_CODE_SUCCESS);
        $transaction->setResponseCode(static::RESPONSE_CODE_SUCCESS);

        return $transaction;
    }

    /**
     * @param \JMS\Payment\CoreBundle\Entity\Payment $payment
     * @return \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface|null
     */
    private function searchForValidTransaction(Payment $payment)
    {
        foreach ($payment->getTransactions() as $t) {
            /** @var FinancialTransactionInterface $t */
            if ($t->getId()) {

                return $t;
            }
        }

        return null;
    }

    /**
     * @param \JMS\Payment\CoreBundle\Entity\PaymentInstruction $instruction
     * @return array
     */
    public function storeData(PaymentInstruction $instruction)
    {
        $data = $this->convertExtendedData($instruction->getExtendedData());

        $reference = $this->client->storeData($data[self::PARAMS_CARD_NUMBER], $data[self::PARAMS_CARD_EXPIRATION_MONTH], $data[self::PARAMS_CARD_EXPIRATION_YEAR]);

        return array(
            $reference,
            substr($data[self::PARAMS_CARD_NUMBER], -4),
        );
    }
}