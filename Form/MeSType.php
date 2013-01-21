<?php

namespace ImmersiveLabs\PaymentMeSBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MeSType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder The builder
     * @param array                $options Options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'payment_mes';
    }
}