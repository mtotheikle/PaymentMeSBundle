<?php

namespace ImmersiveLabs\PaymentMeSBundle\Tests\Client;

use ImmersiveLabs\PaymentMeSBundle\Tests\BaseTestCase;

/**
 * @group exam
 */
class MeSExamTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @dataProvider provider
     */
    public function testExamTPG($inputCardNumber, $expectedInvoice, $expectedTransactionId,  $expectedRefundInvoice, $expectedRefundTransactionId)
    {
        $card = array(
            'cardNumber' => 'dummy',
            'expirationMonth' => '05',
            'expirationYear' => '2017',
            'cvv' => '123',
            'streetAddress' => '123',
            'zip' => '55555',
        );

        $card['cardNumber'] = $inputCardNumber;

        // change cg_profile_id to 94100011317700000015
        // @todo ^^

        // verifies card
        $result = $this->mesClient->verifyCard($card);
        $this->assertTrue(is_array($result));
        $this->assertTrue($result['cvv']);
        $this->assertTrue($result['streetAddress']);
        $this->assertTrue($result['zip']);

        // actual sale transaction
        $actual = $this->mesClient->postSale(
            $card['cardNumber'],
            $card['expirationMonth'],
            $card['expirationYear'],
            0.03
        );

        ladybug_dump($actual);
        // uncomment after getting results for invoice and transactionId
        //$this->assertEquals($expectedInvoice, $actual['invoice']);
        //$this->assertEquals($expectedInvoice, $actual['transactionId']);

        // actual refund transaction
        $actualRefund = $this->mesClient->postRefund(
            $actual['transactionId']
        );

        ladybug_dump($actualRefund);
        // uncomment after getting results for invoice and transactionId
        //$this->assertEquals($expectedRefundInvoice, $actualRefund['invoice']);
        //$this->assertEquals($expectedRefundTransactionId, $actualRefund['transactionId']);
    }

    public function provider()
    {
        return array(
            array('4012301230123010', 0, 0, 0, 0),
            array('5123012301230120', 0, 0, 0, 0),
            array('349999999999991', 0, 0, 0, 0),
            array('6011011231231235', 0, 0, 0, 0)
        );
    }
}
