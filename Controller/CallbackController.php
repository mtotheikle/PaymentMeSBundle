<?php

namespace ETS\Payment\DotpayBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use ETS\Payment\DotpayBundle\Plugin\DotpayDirectPlugin;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;

/**
 * Callback controller
 *
 * @author ETSGlobal <e4-devteam@etsglobal.org>
 */
class CallbackController extends Controller
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request         $request     The request
     * @param \JMS\Payment\CoreBundle\Entity\PaymentInstruction $instruction The payment instruction
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function urlcAction(Request $request, PaymentInstruction $instruction)
    {
        // Check the PIN
        $pin = $this->container->getParameter('payment.dotpay.direct.pin');
        $id = $this->container->getParameter('payment.dotpay.direct.id');
        $logger = $this->get('logger');

        $control = md5(sprintf(
            "%s:%s:%s:%s:%s:%s:%s:%s:%s:%s:%s",
            $pin,
            $id,
            $request->request->get('control'),
            $request->request->get('t_id'),
            $request->request->get('amount'),
            $request->request->get('email'),
            $request->request->get('service'),
            $request->request->get('code'),
            $request->request->get('username'),
            $request->request->get('password'),
            $request->request->get('t_status')
        ));

        if ($control !== $request->request->get('md5')) {
            $logger->err('[Dotpay - URLC] pin verification failed');

            return new Response('FAIL', 500);
        }

        if (null === $transaction = $instruction->getPendingTransaction()) {
            $logger->err('[Dotpay - URLC] no pending transaction found for the payment instruction');

            return new Response('FAIL', 500);
        }

        $transaction->getExtendedData()->set('t_status', $request->get('t_status'));
        $transaction->getExtendedData()->set('t_id', $request->get('t_id'));
        $transaction->getExtendedData()->set('amount', $request->get('amount'));

        try {
            $this->get('payment.plugin.dotpay_direct')->approveAndDeposit($transaction, $request->get('amount'));
        } catch (FinancialException $e) {
            $logger->warn(sprintf('[Dotpay - URLC] %s', $e->getMesssage()));

            return new Response('FAIL');
        }

        $logger->info(sprintf('[Dotpay - URLC] Payment instruction %s successfully updated', $instruction->getId()));

        return new Response('OK');
    }
}
