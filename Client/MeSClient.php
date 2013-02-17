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
        self::TXN_TYPE_SALE     => 'ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident\TpgSale',
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

    public function verifyCard(array $cardInformation)
    {
        $request = new Trident\TpgPreAuth($this->profileId, $this->profileKey);

        $request->RequestFields = array(
            'card_number'               => $cardInformation['cardNumber'],
            'card_exp_date'             => $cardInformation['expirationMonth'] . $cardInformation['expirationYear'],
            'transaction_amount'        => 1.00,
            'cvv2'                      => ($cardInformation['cvv']) ?: '000',
            'cardholder_street_address' => $cardInformation['streetAddress'],
            'cardholder_zipcode'        => $cardInformation['zip'],
        );

        $request->execute();

        if ($request->ResponseFields['error_code'] == '000' && $request->ResponseFields['auth_response_text'] != 'No Match') {
            $transactionId = $request->ResponseFields['transaction_id'];

            $voidTransaction = new Trident\TpgVoid($this->profileId, $this->profileKey, $transactionId);
            $voidTransaction->execute();

            return true;
        }


        // otherwise we return an array with errors (check Payment MeS Gateway PDF page: 39)
        $errors = array(
            'cvv' => true,
            'zip' => true,
            'streetAddress' => true,
        );

        if ($request->ResponseFields['error_code'] == '014') {

            return $errors;
        }

        if (isset($request->ResponseFields['cvv2_result'])) {
            if ($request->ResponseFields['cvv2_result'] == 'M') {
                $errors['cvv'] = false;
            }
        }

        if (isset($request->ResponseFields['avs_result'])) {
            if ($request->ResponseFields['avs_result'] == 'M' || $request->ResponseFields['avs_result'] == 'Y') {
                $errors['streetAddress'] = $errors['zip'] = false;
            } elseif ($request->ResponseFields['avs_result'] == 'Z') {
                $errors['zip'] = false;
            } elseif ($request->ResponseFields['avs_result'] == 'A') {
                $errors['streetAddress'] = $errors['zip'] = false;
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

        if ($txn->ResponseFields['error_code'] == '000') {

            return $txn->ResponseFields['transaction_id'];
        }

        return false;
    }
}
