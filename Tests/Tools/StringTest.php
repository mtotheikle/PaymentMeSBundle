<?php

namespace ETS\PurchaseBundle\Tests\Tools;

use ETS\Payment\DotpayBundle\Tools\String;

/**
 * String Tests
 *
 * @author ETSGlobal <e4-devteam@etsglobal.org>
 */
class StringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function normalizeProvider()
    {
        return array(
            array("Clément", "Clement"),
            array("eéèêëiîïoöôuùûüaâäÅ Ἥ ŐǟǠ ǺƶƈƉųŪŧȬƀ␢ĦŁȽŦ ƀǖ", "eeeeeiiiooouuuuaaaA Η OaA AƶƈƉuUŧOƀ␢ĦŁȽŦ ƀu"),
            array("Fóø Bår", "Foø Bar"),
        );
    }

    /**
     * @param string $input    Input string
     * @param string $expected Expected output string
     *
     * @dataProvider normalizeProvider
     */
    public function testNormaize($input, $expected)
    {
        $tool = new String();

        $this->assertEquals($expected, $tool->normalize($input));
    }
}
