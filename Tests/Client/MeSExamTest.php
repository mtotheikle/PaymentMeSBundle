<?php

namespace ImmersiveLabs\PaymentMeSBundle\Tests\Client;

use ImmersiveLabs\PaymentMeSBundle\Tests\BaseTestCase;
use ImmersiveLabs\PaymentMeSBundle\Client\MeSClient;

/**
 * @group exam
 */
class MeSExamTest extends BaseTestCase
{
    /** @var MeSClient */
    protected $mesClient;

    public function setUp()
    {
        parent::setUp();
    }

    public function testExam()
    {
        $cardNumbers = array(
            '4012301230123010',
            '5123012301230120',
            '349999999999991',
            '6011011231231235'
        );

        foreach ($cardNumbers as $cardNumber) {
            ladybug_dump(sprintf('Results for %s', $cardNumber));
            $result = $this->mesClient->postSale($cardNumber, '05', '2017', 0.03);

            ladybug_dump($result->ResponseFields);

            $result = $this->mesClient->postRefund($result->ResponseFields['transaction_id']);
            ladybug_dump($result->ResponseFields);
        }
    }
}
