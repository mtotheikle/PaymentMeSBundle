<?php

namespace ImmersiveLabs\PaymentMeSBundle\Tests\Client;

use ImmersiveLabs\PaymentMeSBundle\Tests\BaseTestCase;

/**
 * @group mes
 */
class MeSClientTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testVerifyCard()
    {
        $card = array(
            'cardNumber' => '4111111111111111',
            'expirationMonth' => '05',
            'expirationYear' => '2017',
            'cvv' => '123',
            'streetAddress' => '123',
            'zip' => '55555'
        );

        $result = $this->mesClient->verifyCard($card);

        $this->assertTrue($result);

        $card['cardNumber'] = '4111111111111112';
        $result = $this->mesClient->verifyCard($card);
        $this->assertTrue(is_array($result));
        $this->assertTrue($result['cvv']);
        $this->assertTrue($result['streetAddress']);
        $this->assertTrue($result['zip']);

        $card['cardNumber'] = '4111111111111111';
        $card['cvv'] = '412';
        $result = $this->mesClient->verifyCard($card);
        $this->assertTrue($result['cvv']);
        $this->assertFalse($result['streetAddress']);
        $this->assertFalse($result['zip']);

        $card['streetAddress'] = 'ABC DEF';
        $result = $this->mesClient->verifyCard($card);
        $this->assertTrue(is_array($result));
        $this->assertTrue($result['streetAddress']);
        $this->assertTrue($result['zip']);

        $card['zip'] = '10101';
        $result = $this->mesClient->verifyCard($card);
        $this->assertTrue(is_array($result));
        $this->assertTrue($result['streetAddress']);
        $this->assertTrue($result['zip']);
    }
}
