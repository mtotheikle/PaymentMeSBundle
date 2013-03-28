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
        $transaction->setHost($this->apiUrl);
        $transaction->setTransactionData($cardNumber, $expirationMonth . $expirationYear, $amount);
        $transaction->execute();

        if (!$transaction->isApproved()) {
            return false;
        }

        return $transaction;
    }

    public function postRefund($transactionId)
    {
        $transaction = new Trident\TpgRefund($this->profileId, $this->profileKey, $transactionId);
        $transaction->setHost($this->apiUrl);
        $transaction->execute();

        if (!$transaction->isApproved()) {
            return false;
        }

        return $transaction;
    }

    public function verifyCard(array $cardInformation)
    {
        $request = new Trident\TpgTransaction($this->profileId, $this->profileKey);
        $request->setHost($this->apiUrl);
        $request->setTransactionData($cardInformation['cardNumber'], $cardInformation['expirationMonth'] . $cardInformation['expirationYear']);
        $request->setAvsRequest($cardInformation['streetAddress'], $cardInformation['zip']);
        $request->setRequestField('cvv2', $cardInformation['cvv']);

        $request->execute();

        if ($request->ResponseFields['error_code'] == self::CODE_CARD_OK) {

            return array(
                'cvv'           => false,
                'zip'           => false,
                'streetAddress' => false,
                'cardError'     => false
            );
        }

        $errors = array(
            'cvv'           => true,
            'zip'           => true,
            'streetAddress' => true,
            'cardError'     => true,
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
        $settle->setHost($this->apiUrl);
        $settle->execute();

        if (!$settle->isApproved()) {
            return false;
        }

        return $settle;
    }

    public function postVoid($transactionId)
    {
        $void = new Trident\TpgVoid($this->profileId, $this->profileKey, $transactionId);
        $void->setHost($this->apiUrl);
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
        $txn->setHost($this->apiUrl);
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
        $txn->setHost($this->apiUrl);
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
        $txn->setHost($this->apiUrl);
        $txn->RequestFields['card_number'] = $cardNumber;
        $txn->RequestFields['card_exp_date'] = $expirationMonth . $expirationYear;

        $txn->execute();

        if ($txn->ResponseFields['error_code'] == self::NO_ERROR) {

            return $txn->ResponseFields['transaction_id'];
        }

        return false;
    }
}
