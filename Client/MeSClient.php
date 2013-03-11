<?php

namespace ImmersiveLabs\PaymentMeSBundle\Client;

use ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident;

class MeSClient
{
    const TXN_TYPE_SALE = 'sale';
    const TXN_TYPE_PRE_AUTH = 'pre-auth';
    const TXN_TYPE_STOREDATA = 'store-data';

    const NO_ERROR = '000';
    const CARD_NUMBER_ERROR = '014';
    const CODE_CARD_OK = '085';

    protected $profileId;
    protected $profileKey;
    protected $apiUrl;

    protected $txnMappings = array(
        self::TXN_TYPE_SALE     => 'ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident\TpgSale',
        self::TXN_TYPE_PRE_AUTH => 'ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident\TpgPreAuth'
    );

    public function __construct($auth)
    {
        list($this->profileId, $this->profileKey, $this->apiUrl) = $auth;
    }

    public function postSale($cardNumber, $expirationMonth, $expirationYear, $amount)
    {
        $transaction = new Trident\TpgSale($this->profileId, $this->profileKey);

        $transaction->setTransactionData($cardNumber, $expirationMonth . $expirationYear, $amount);
        $transaction->execute();

        return $transaction;
    }

    public function postRefund($transactionId)
    {
        $transaction = new Trident\TpgRefund($this->profileId, $this->profileKey, $transactionId);
        $transaction->execute();

        return $transaction;
    }

    public function verifyCard(array $cardInformation)
    {
        $request = new Trident\TpgTransaction($this->profileId, $this->profileKey);

        $request->RequestFields = array(
            'card_number'               => $cardInformation['cardNumber'],
            'card_exp_date'             => $cardInformation['expirationMonth'] . $cardInformation['expirationYear'],
            'transaction_amount'        => 0.00,
            'transaction_type'          => 'A',
            'cvv2'                      => $cardInformation['cvv'],
            'cardholder_street_address' => $cardInformation['streetAddress'],
            'cardholder_zipcode'        => $cardInformation['zip'],
        );

        $request->execute();

        if ($request->ResponseFields['error_code'] == self::CODE_CARD_OK) {

            return true;
        }

        $errors = array(
            'cvv' => true,
            'zip' => true,
            'streetAddress' => true,
            'cardError' => true,
        );

        if (isset($request->ResponseFields['cvv2_result'])) {
            if ($request->ResponseFields['cvv2_result'] == 'M') {
                $errors['cvv'] = false;
            }
        }

        if (isset($request->ResponseFields['avs_result'])) {
            $result = $request->ResponseFields['avs_result'];

            if (in_array($result, array('M', 'Y', 'A'))) {
                $errors['streetAddress'] = $errors['zip'] = false;
            } elseif ($result == 'Z') {
                $errors['zip'] = false;
            }
        }

        return $errors;
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

        return $settle;
    }

    public function postVoid($transactionId)
    {
        $void = new Trident\TpgVoid($this->profileId, $this->profileKey, $transactionId);
        $void->execute();

        if (!$void->isApproved()) {
            return false;
        }

        return $void;
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

        return $txn;
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

        return $txn;
    }

    public function storeData($cardNumber, $expirationMonth, $expirationYear)
    {
        $txn = new Trident\TpgStoreData($this->profileId, $this->profileKey);
        $txn->RequestFields['card_number'] = $cardNumber;
        $txn->RequestFields['card_exp_date'] = $expirationMonth . $expirationYear;

        $txn->execute();

        if ($txn->ResponseFields['error_code'] == self::NO_ERROR) {

            return $txn->ResponseFields['transaction_id'];
        }

        return false;
    }
}
