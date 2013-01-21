<?php

namespace Infinite\Payment\PayWayBundle\Plugin;

use Infinite\Payment\PayWayBundle\Client\Client;
use Infinite\Payment\PayWayBundle\Client\Response;
use Infinite\Payment\PayWayBundle\NumberGenerator\GeneratorInterface;
use Infinite\Payment\PayWayBundle\Plugin\Exception\ReversalCutoffException;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Plugin\ErrorBuilder;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use DateTime;

class PayWayPlugin extends AbstractPlugin
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var GeneratorInterface
     */
    private $generator;

    /**
     * Constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client, GeneratorInterface $generator)
    {
        $this->client    = $client;
        $this->generator = $generator;
    }

    /**
     * Checks the payment instruction for valid fields.
     *
     * @param PaymentInstructionInterface $instruction
     */
    public function checkPaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $errorBuilder = new ErrorBuilder;
        $data         = $instruction->getExtendedData();

        $this->checkRequired($errorBuilder, $data);

        if ($data->has('expiryMonth') && $data->has('expiryYear')) {
            $expiry = DateTime::createFromFormat('d/m/y', sprintf(
                '1/%d/%d', $data->get('expiryMonth'), $data->get('expiryYear')
            ));
            $expiry->setTime(0,0,0);
            $current = new DateTime;
            $current->setTime(0,0,0);
            $current->setDate($current->format('Y'), $current->format('m'), 1);

            if ($expiry < $current) {
                $errorBuilder->addDataError('data_infinite_payment_payway_api.expiryYear', 'expired');
            }
        }

        if ($errorBuilder->hasErrors()) {
            throw $errorBuilder->getException();
        }
    }

    /**
     * Checks that all required fields are present in the ExtendedData object,
     * adding form errors if they are not present.
     *
     * @param \JMS\Payment\CoreBundle\Plugin\ErrorBuilder         $errorBuilder
     * @param \JMS\Payment\CoreBundle\Model\ExtendedDataInterface $data
     */
    protected function checkRequired(ErrorBuilder $errorBuilder, ExtendedDataInterface $data)
    {
        $fields = array('pan', 'securityCode', 'cardHolderName', 'expiryMonth', 'expiryYear');

        foreach ($fields as $field) {
            if (!($data->has($field) and $data->get($field))) {
                $errorBuilder->addDataError(sprintf('data_infinite_payment_payway_api.%s', $field), 'required');
            }
        }
    }

    /**
     * Pre-authorises a transaction.
     *
     * @param FinancialTransactionInterface $transaction
     * @param bool                          $retry
     */
    public function approve(FinancialTransactionInterface $transaction, $retry)
    {
        $this->setUniqueReferenceNumber($transaction);
        $amount = $transaction->getRequestedAmount();
        $data   = $this->convertExtendedData($transaction->getExtendedData());

        $response = $this->client->requestPreAuth($transaction->getReferenceNumber(), $amount, $data);
        $this->setData($transaction, $response);
        $this->throwUnlessSuccessResponse($response, $transaction);

        $transaction->setProcessedAmount($amount);
        $transaction->setReasonCode(static::REASON_CODE_SUCCESS);
        $transaction->setResponseCode(static::RESPONSE_CODE_SUCCESS);
    }

    /**
     * Captures a payment without needing to pre-authorise.
     *
     * @param FinancialTransactionInterface $transaction
     * @param bool                          $retry
     */
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $this->setUniqueReferenceNumber($transaction);
        $amount = $transaction->getRequestedAmount();
        $data   = $this->convertExtendedData($transaction->getExtendedData());

        $response = $this->client->requestCapture($transaction->getReferenceNumber(), $amount, $data);
        $this->setData($transaction, $response);
        $this->throwUnlessSuccessResponse($response, $transaction);

        $transaction->setProcessedAmount($amount);
        $transaction->setReasonCode(static::REASON_CODE_SUCCESS);
        $transaction->setResponseCode(static::RESPONSE_CODE_SUCCESS);
    }

    /**
     * Issues a refund.
     *
     * @param FinancialTransactionInterface $transaction
     * @param bool                          $retry
     */
    public function credit(FinancialTransactionInterface $transaction, $retry)
    {
        $this->setUniqueReferenceNumber($transaction);
        $amount              = $transaction->getRequestedAmount();
        $data                = $this->convertExtendedData($transaction->getExtendedData());
        $originalOrderNumber = $transaction->getPayment()->getApproveTransaction()->getReferenceNumber();

        $response = $this->client->requestRefund(
            $originalOrderNumber, $transaction->getReferenceNumber(), $amount, $data
        );
        $this->setData($transaction, $response);
        $this->throwUnlessSuccessResponse($response, $transaction);

        $transaction->setProcessedAmount($amount);
        $transaction->setReasonCode(static::REASON_CODE_SUCCESS);
        $transaction->setResponseCode(static::RESPONSE_CODE_SUCCESS);
    }

    /**
     * Captures a pre-authorised transaction.
     *
     * @param FinancialTransactionInterface $transaction
     * @param bool                          $retry
     */
    public function deposit(FinancialTransactionInterface $transaction, $retry)
    {
        $this->setUniqueReferenceNumber($transaction);
        $amount              = $transaction->getRequestedAmount();
        $originalOrderNumber = $transaction->getPayment()->getApproveTransaction()->getReferenceNumber();

        $response = $this->client->requestCaptureWithoutAuth($originalOrderNumber, $transaction->getReferenceNumber(), $amount);
        $this->throwUnlessSuccessResponse($response, $transaction);
        $this->setData($transaction, $response);

        $transaction->setProcessedAmount($amount);
        $transaction->setReasonCode(static::REASON_CODE_SUCCESS);
        $transaction->setResponseCode(static::RESPONSE_CODE_SUCCESS);
    }

    /**
     * Reverses an approval.
     *
     * @param FinancialTransactionInterface $transaction
     * @param bool                          $retry
     */
    public function reverseApproval(FinancialTransactionInterface $transaction, $retry)
    {
        $this->doReverse($transaction, $transaction->getPayment()->getApproveTransaction());
    }

    /**
     * Reverses a refund.
     *
     * @param FinancialTransactionInterface $transaction
     * @param bool                          $retry
     */
    public function reverseCredit(FinancialTransactionInterface $transaction, $retry)
    {
        // TODO: implement
        parent::reverseCredit($transaction, $retry);
    }

    /**
     * Reverses a credit.
     *
     * @param FinancialTransactionInterface $transaction
     * @param bool                          $retry
     */
    public function reverseDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        // TODO: implement
        parent::reverseDeposit($transaction, $retry);
    }

    /**
     * Performs the actual reversal.
     *
     * @param  FinancialTransactionInterface     $transaction
     * @param  FinancialTransactionInterface     $transactionToReverse
     * @throws Exception\ReversalCutoffException
     */
    protected function doReverse(FinancialTransactionInterface $transaction, FinancialTransactionInterface $transactionToReverse)
    {
        $this->setUniqueReferenceNumber($transaction);
        $amount              = $transaction->getRequestedAmount();
        $data                = $this->convertExtendedData($transaction->getExtendedData());
        $originalOrderNumber = $transactionToReverse->getReferenceNumber();

        if ($settlement = $transactionToReverse->getExtendedData()->get('settlementDate')) {
            $settlement = \DateTime::createFromFormat('Ymd', $settlement);
            $settlement->setTime(18, 0);
            $now        = new \DateTime;

            if ($settlement < $now) {
                throw new ReversalCutoffException(
                    sprintf('%s is after the settlement cutoff of 6PM', $now->format('r'))
                );
            }
        }

        $response = $this->client->requestReversal($originalOrderNumber, $transaction->getReferenceNumber());
        $this->setData($transaction, $response);
        $this->throwUnlessSuccessResponse($response, $transaction);

        $transaction->setProcessedAmount($amount);
        $transaction->setReasonCode(static::REASON_CODE_SUCCESS);
        $transaction->setResponseCode(static::RESPONSE_CODE_SUCCESS);
    }

    /**
     * Converts an ExtendedData object's data to an array supported by the PayWay Client.
     *
     * @param  \JMS\Payment\CoreBundle\Model\ExtendedDataInterface $data
     * @return array
     */
    protected function convertExtendedData(ExtendedDataInterface $data)
    {
        $array = array();

        if ($data->has('pan')) {
            $array['card.PAN'] = $data->get('pan');
        }

        if ($data->has('securityCode')) {
            $array['card.CVN'] = $data->get('securityCode');
        }

        if ($data->has('expiryMonth')) {
            $array['card.expiryMonth'] = $data->get('expiryMonth');
        }

        if ($data->has('expiryYear')) {
            $array['card.expiryYear'] = $data->get('expiryYear');
        }

        if ($data->has('cardHolderName')) {
            $array['card.cardHolderName'] = $data->get('cardHolderName');
        }

        return $array;
    }

    /**
     * Takes relevant fields from the Response and sets them on the Transaction's
     * ExtendedData object.
     *
     * @param ExtendedDataInterface $data
     * @param Response              $response
     */
    protected function setData(FinancialTransactionInterface $transaction, Response $response)
    {
        $extendedData = $transaction->getExtendedData();
        $extendedData->set('receiptNo', $response->body->get('response_receiptNo'));
        $extendedData->set('responseCode', $response->body->get('response_responseCode'));
        $extendedData->set('settlementDate', $response->body->get('response_settlementDate'));
        $extendedData->set('transactionDate', $response->body->get('response_transactionDate'));
        $extendedData->set('text', $response->body->get('response_text'), false, true);
        $extendedData->set('cvnResponse', $response->body->get('response_cvnResponse'));

        $transaction->setExtendedData($extendedData);
    }

    /**
     * Checks if we support a given payment system.
     *
     * @param  string  $paymentSystemName
     * @return boolean
     */
    public function processes($paymentSystemName)
    {
        return 'infinite_payment_payway_api' === $paymentSystemName;
    }

    /**
     * Checks for a successful transaction and if unsuccessful, sets up the transaction's
     * Response and Reason codes then throws a FinancialException.
     *
     * @param  Response                      $response
     * @param  FinancialTransactionInterface $transaction
     * @throws FinancialException
     */
    protected function throwUnlessSuccessResponse(Response $response, FinancialTransactionInterface $transaction)
    {
        if ($response->isSuccess()) {
            return;
        }

        $transaction->setResponseCode($response->body->get('response.responseCode'));
        $transaction->setReasonCode($response->body->get('response.text'));

        $ex = new FinancialException('PayWay-Response was not successful: '.$response);
        $ex->setFinancialTransaction($transaction);

        throw $ex;
    }

    /**
     * Sets a unique reference number if one is not already set.
     *
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     */
    protected function setUniqueReferenceNumber(FinancialTransactionInterface $transaction)
    {
        if (null === $transaction->getReferenceNumber()) {
            $transaction->setReferenceNumber($this->generator->generate($transaction));
        }
    }

    /**
     * Payway does not support independent credits.
     *
     * @return bool
     */
    public function isIndependentCreditSupported()
    {
        return false;
    }
}