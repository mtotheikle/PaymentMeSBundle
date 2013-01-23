<?php

namespace ImmersiveLabs\PaymentMeSBundle\Plugin;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;

class MeSPlugin extends AbstractPlugin
{
    protected $pgProfileId;
    protected $pgProfileKey;
    protected $pgHost;

    /**
     * @param string $pgProfileId
     * @param string $pgProfileKey
     * @param string $pgHost
     */
    public function __construct($pgProfileId, $pgProfileKey, $pgHost)
    {
        $this->pgProfileId = $pgProfileId;
        $this->pgProfileKey = $pgProfileKey;
        $this->pgHost = $pgHost;
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
        $this->setUniqueReferenceNumber($transaction);
        $amount = $transaction->getRequestedAmount();
//        $data   = $this->convertExtendedData($transaction->getExtendedData());

//        $response = $this->client->requestCapture($transaction->getReferenceNumber(), $amount, $data);
//        $this->setData($transaction, $response);
//        $this->throwUnlessSuccessResponse($response, $transaction);

        $transaction->setProcessedAmount($amount);
        $transaction->setReasonCode(static::REASON_CODE_SUCCESS);
        $transaction->setResponseCode(static::RESPONSE_CODE_SUCCESS);
    }

    /**
     * Sets a unique reference number if one is not already set.
     *
     * @param \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface $transaction
     */
    protected function setUniqueReferenceNumber(FinancialTransactionInterface $transaction)
    {
        if (null === $transaction->getReferenceNumber()) {
            $transaction->setReferenceNumber(uniqid('FTI'));
        }
    }

    /**
     * This method executes an approve transaction.
     *
     * By an approval, funds are reserved but no actual money is transferred. A
     * subsequent deposit transaction must be performed to actually transfer the
     * money.
     *
     * A typical use case, would be Credit Card payments where funds are first
     * authorized.
     *
     * @param FinancialTransactionInterface $transaction The transaction
     * @param boolean                       $retry       Retry
     */
    public function approve(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();

        switch ($data->get('t_status')) {
            case self::STATUS_NEW:
                // TODO: The status should not be NEW at this point, I think
                // we should throw an Exception that trigger the PENDING state
            case self::STATUS_COMPLAINT:
                // TODO: What is this status ? should we deal with it ?
            case self::STATUS_DONE:
            case self::STATUS_CLOSED:
            case self::STATUS_REFUND:
            case self::STATUS_REJECTED:
                break;

            default:
                $ex = new FinancialException('Payment status unknow: '.$data->get('t_status'));
                $ex->setFinancialTransaction($transaction);
                $transaction->setResponseCode('Unknown');

                throw $ex;
        }

        $transaction->setReferenceNumber($data->get('t_id'));
        $transaction->setProcessedAmount($data->get('amount'));
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
    }
}