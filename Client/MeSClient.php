<?php

namespace ImmersiveLabs\PaymentMeSBundle\Client;

use ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class MeSClient
{
    const TXN_TYPE_SALE = 'sale';
    const TXN_TYPE_PRE_AUTH = 'pre-auth';
    const TXN_TYPE_STOREDATA = 'store-data';

    protected $profileId;
    protected $profileKey;
    protected $apiUrl;

    protected $txnMappings = array(
        self::TXN_TYPE_SALE => 'ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident\TpgSale',
        self::TXN_TYPE_PRE_AUTH => 'ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident\TpgPreAuth'
    );
    public function __construct($auth)
    {
        list($this->profileId, $this->profileKey, $this->apiUrl) = $auth;
    }

    public function postSale($cardNumber, $expirationMonth, $expirationYear, $amount)
    {
        return $this->adhocTxnExecute(self::TXN_TYPE_SALE, $cardNumber, $expirationMonth, $expirationYear, $amount);
    }

    public function postSaleForStoredData($cardId, $amount)
    {
        return $this->storedDataTxnExecute(self::TXN_TYPE_SALE, $cardId, $amount);
    }

    public function postPreAuth($cardNumber, $expirationMonth, $expirationYear, $amount)
    {
        return $this->adhocTxnExecute(self::TXN_TYPE_PRE_AUTH, $cardNumber, $expirationMonth, $expirationYear, $amount);
    }

    public function postPreAuthForStoredData($cardId, $amount)
    {
        return $this->storedDataTxnExecute(self::TXN_TYPE_PRE_AUTH, $cardId, $amount);
    }

    public function postSettle($transactionId, $amount)
    {
        $settle = new Trident\TpgSettle($this->profileId, $this->profileKey, $transactionId, $amount);

        $settle->execute();

        if (!$settle->isApproved()) {
            return false;
        }

        return $settle->ResponseFields['transaction_id'];
    }

    public function postVoid($transactionId)
    {
        $void = new Trident\TpgVoid($this->profileId, $this->profileKey, $transactionId);
        $void->execute();

        if (!$void->isApproved()) {
            return false;
        }

        return $void->ResponseFields['transaction_id'];
    }

    public function postRefund($transactionId)
    {
        $refund = new Trident\TpgRefund($this->profileId, $this->profileKey, $transactionId);
        $refund->execute();

        if (!$refund->isApproved()) {
            return false;
        }

        return $refund->ResponseFields['transaction_id'];
    }

    protected function storedDataTxnExecute($txnType, $cardId, $amount)
    {
        $txnClass = $this->txnMappings[$txnType];

        $txn = new $txnClass($this->profileId, $this->profileKey);

        $txn->setStoredData($cardId, $amount);
        $txn->execute();

        if (!$txn->isApproved()) {
            return false;
        }

        return $txn->ResponseFields['transaction_id'];
    }

    protected function adhocTxnExecute($txnType, $cardNumber, $expirationMonth, $expirationYear, $amount)
    {
        $txnClass = $this->txnMappings[$txnType];

        $txn = new $txnClass($this->profileId, $this->profileKey);

        $txn->setTransactionData($cardNumber, $expirationMonth . $expirationYear, $amount);
        $txn->execute();

        if (!$txn->isApproved()) {
            return false;
        }

        return $txn->ResponseFields['transaction_id'];
    }

    public function storeData($cardNumber, $expirationMonth, $expirationYear)
    {
        $txn = new Trident\TpgStoreData($this->profileId, $this->profileKey);
        $txn->RequestFields['card_number'] = $cardNumber;
        $txn->RequestFields['card_exp_date'] = $expirationMonth . $expirationYear;

        $txn->execute();

        return $txn->ResponseFields['transaction_id'];
    }
}
