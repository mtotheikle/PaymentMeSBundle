<?php
namespace ImmersiveLabs\PaymentMeSBundle\Client;

use ImmersiveLabs\PaymentMeSBundle\PaymentGateway\Trident\TpgSale;
use Symfony\Component\BrowserKit\Response as RawResponse;

use JMS\Payment\CoreBundle\BrowserKit\Request;
use JMS\Payment\CoreBundle\Plugin\Exception\CommunicationException;
use JMS\Payment\PaypalBundle\Client\Authentication\AuthenticationStrategyInterface;

class Client
{
    const API_VERSION = '65.1';

    protected $authenticationStrategy;

    protected $isDebug;

    protected $curlOptions;

    public function __construct(AuthenticationStrategyInterface $authenticationStrategy, $isDebug)
    {
        $this->authenticationStrategy = $authenticationStrategy;
        $this->isDebug = !!$isDebug;
        $this->curlOptions = array();
    }


    public function chargePayment()
    {
        $sale = new TpgSale();
        $sale->setHost();

        return $this->sendApiRequest(array_merge($optionalParameters, array(
            'METHOD' => 'DoVoid',
            'AUTHORIZATIONID' => $authorizationId,
        )));
    }

    /**
     * Initiates an ExpressCheckout payment process
     *
     * Optional parameters can be found here:
     * https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_SetExpressCheckout
     *
     * @param float $amount
     * @param string $returnUrl
     * @param string $cancelUrl
     * @param array $optionalParameters
     * @return Response
     */
    public function requestSetExpressCheckout($amount, $returnUrl, $cancelUrl, array $optionalParameters = array())
    {
        return $this->sendApiRequest(array_merge($optionalParameters, array(
            'METHOD' => 'SetExpressCheckout',
            'PAYMENTREQUEST_0_AMT' => $this->convertAmountToPaypalFormat($amount),
            'RETURNURL' => $returnUrl,
            'CANCELURL' => $cancelUrl,
        )));
    }

    public function requestGetExpressCheckoutDetails($token)
    {
        return $this->sendApiRequest(array(
            'METHOD' => 'GetExpressCheckoutDetails',
            'TOKEN'  => $token,
        ));
    }

    public function requestGetTransactionDetails($transactionId)
    {
        return $this->sendApiRequest(array(
            'METHOD' => 'GetTransactionDetails',
            'TRANSACTIONID' => $transactionId,
        ));
    }

    public function requestRefundTransaction($transactionId, array $optionalParameters = array())
    {
        return $this->sendApiRequest(array_merge($optionalParameters, array(
            'METHOD' => 'RefundTransaction',
            'TRANSACTIONID' => $transactionId
        )));
    }

    public function sendApiRequest(array $parameters)
    {
        // include some default parameters
        $parameters['VERSION'] = self::API_VERSION;

        // setup request, and authenticate it
        $request = new Request(
            $this->authenticationStrategy->getApiEndpoint($this->isDebug),
            'POST',
            $parameters
        );
        $this->authenticationStrategy->authenticate($request);

        $response = $this->request($request);
        if (200 !== $response->getStatus()) {
            throw new CommunicationException('The API request was not successful (Status: '.$response->getStatus().'): '.$response->getContent());
        }

        $parameters = array();
        parse_str($response->getContent(), $parameters);

        return new Response($parameters);
    }

    public function getAuthenticateExpressCheckoutTokenUrl($token)
    {
        $host = $this->isDebug ? 'www.sandbox.paypal.com' : 'www.paypal.com';

        return sprintf(
            'https://%s/cgi-bin/webscr?cmd=_express-checkout&token=%s',
            $host,
            $token
        );
    }

    public function convertAmountToPaypalFormat($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    public function setCurlOption($name, $value)
    {
        $this->curlOptions[$name] = $value;
    }

    /**
     * A small helper to url-encode an array
     *
     * @param array $encode
     * @return string
     */
    protected function urlEncodeArray(array $encode)
    {
        $encoded = '';
        foreach ($encode as $name => $value) {
            $encoded .= '&'.urlencode($name).'='.urlencode($value);
        }

        return substr($encoded, 1);
    }

    /**
     * Performs a request to an external payment service
     *
     * @throws CommunicationException when an curl error occurs
     * @param Request $request
     * @param mixed $parameters either an array for form-data, or an url-encoded string
     * @return Response
     */
    public function request(Request $request)
    {

        return $response;
    }
}
