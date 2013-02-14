<?php

namespace ImmersiveLabs\PaymentMeSBundle\Tests\Plugin;

use ImmersiveLabs\CaraCore\Tests\TestBaseManager;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Entity\Credit;
use Vespolina\Entity\Partner\PaymentProfile;
/**
 * @group mes-plugin
 */
class MeSPluginTest extends TestBaseManager {

    /**
     * @group mes-plugin-capture
     */
    public function testCapture()
    {
        $this->capture();
    }

    /**
     * @group mes-plugin-refund
     */
    public function testRefund()
    {
        $ft = $this->capture();

        /** @var Payment $payment  */
        $payment = $ft->getPayment();

        /** @var Credit $credit  */
        $credit = $this->getPaymentPluginController()->createDependentCredit($payment->getId(), $payment->getTargetAmount());

        /** @var Result $result  */
        $result = $this->getPaymentPluginController()->credit($credit->getId(), $payment->getTargetAmount());

        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());

        /** @var FinancialTransactionInterface $ft  */
        $ft = $result->getFinancialTransaction();

        $this->assertNotNull($ft->getReferenceNumber());
    }

    /**
     * @group mes-plugin-void
     */
    public function testVoid()
    {
        $financialTransaction = $this->authorize();
        /** @var Payment $payment  */
        $payment = $financialTransaction->getPayment();

        $reverseResult = $this->getPaymentPluginController()->reverseApproval($payment->getId(), 0);

        $this->assertEquals(Result::STATUS_SUCCESS, $reverseResult->getStatus());

        /** @var FinancialTransactionInterface $rFinancialTransaction  */
        $rFinancialTransaction = $reverseResult->getFinancialTransaction();

        $this->assertNotNull($rFinancialTransaction->getReferenceNumber());
    }

    /**
     * @group mes-plugin-store
     */
    public function testStore()
    {
        $plugin = $this->getMESPaymentPlugin();

        $paymentInstruction = $this->createPaymentInstruction();

        list($reference, $last4Digits) = $plugin->storeData($paymentInstruction);

        $paymentInstruction = $this->createPaymentInstruction($reference);
        $payment = $this->createPayment($paymentInstruction);

        $result = $this->getPaymentPluginController()->approveAndDeposit($payment->getId(), $payment->getTargetAmount());

        /** @var FinancialTransactionInterface $ft  */
        $ft = $result->getFinancialTransaction();

        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertNotNull($ft->getReferenceNumber());
    }

    /**
     * @return \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface
     */
    private function capture()
    {
        $transaction = $this->authorize();
        sleep(5);
        /** @var Payment $payment  */
        $payment = $transaction->getPayment();

        $result = $this->getPaymentPluginController()->deposit($payment->getId(), $payment->getTargetAmount());

        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());

        /** @var FinancialTransactionInterface $ft  */
        $ft = $result->getFinancialTransaction();

        $this->assertNotNull($ft->getReferenceNumber());

        return $ft;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface
     */
    private function authorize()
    {
        $ins = $this->createPaymentInstruction();
        $payment = $this->createPayment($ins);
        $result = $this->getPaymentPluginController()->approve($payment->getId(), $payment->getTargetAmount());

        /** @var FinancialTransactionInterface $financialTransaction  */
        $financialTransaction = $result->getFinancialTransaction();

        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertNotNull($financialTransaction->getReferenceNumber());

        return $financialTransaction;
    }

    /**
     * @param \JMS\Payment\CoreBundle\Entity\PaymentInstruction $ins
     * @return \JMS\Payment\CoreBundle\Model\PaymentInterface
     */
    private function createPayment(PaymentInstruction $ins)
    {
        return $this->getPaymentPluginController()->createPayment($ins->getId(), $ins->getAmount());
    }

    /**
     * @param null $cardReference
     * @return \JMS\Payment\CoreBundle\Entity\PaymentInstruction
     */
    private function createPaymentInstruction($cardReference = null)
    {
        $exp = $this->getSampleExpirationDate();
        $month = $exp->format('m');
        $year = $exp->format('y');
        $extendedData = new ExtendedData();
        if (empty($cardReference)) {
            $extendedData->set('cardNumber', $this->getSampleCardNumber(), false, false);
            $extendedData->set('cvv', $this->getSampleCvv(), false, false);
            $extendedData->set('expirationMonth', $month, false, false);
            $extendedData->set('expirationYear', $year, false, false);
        } else {
            $extendedData->set('cardId', $cardReference, false, false);
        }

        $ins = new PaymentInstruction(50, 'USD', 'payment_mes', $extendedData);
        $this->getPaymentPluginController()->createPaymentInstruction($ins);

        return $ins;
    }

    /**
     * @return string
     */
    private function getSampleCvv()
    {
        return '123';
    }

    /**
     * @return string
     */
    private function getSampleCardNumber()
    {
        return '4111111111111111';
    }

    /**
     * @return \DateTime
     */
    private function getSampleExpirationDate()
    {
        return new \DateTime();
    }
}
