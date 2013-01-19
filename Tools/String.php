<?php

namespace ImmersiveLabs\PaymentMeSBundle;

/**
 * String tools
 */
class String
{
    /**
     * Remove all unwanted caracters
     *
     * @param string $text
     *
     * @return string
     */
    public function normalize($text)
    {
        return preg_replace('/\pM*/u', '', normalizer_normalize($text, \Normalizer::FORM_D));
    }
}
