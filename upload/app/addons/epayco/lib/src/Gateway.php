<?php
namespace Omnipay\Epayco;

use Omnipay\Common\AbstractGateway;

/**
 * Epayco Gateway
 *
 * This gateway is useful for testing. It implements all the functions listed in \Omnipay\Common\GatewayInterface
 * and allows both successful and failed responses based on the input.
 *
 * For authorize(), purchase(), and createCard() functions ...
 *
 *    Any card number which passes the Luhn algorithm and ends in an even number is authorized,
 *    for example: 4242424242424242
 *
 *    Any card number which passes the Luhn algorithm and ends in an odd number is declined,
 *    for example: 4111111111111111
 *
 * For capture(), completeAuthorize(), completePurchase(), refund(), and void() functions...
 *    A transactionReference option is required. If the transactionReference contains 'fail', the
 *    request fails. For any other values, the request succeeds
 *
 * For updateCard() and deleteCard() functions...
 *    A cardReference field is required. If the cardReference contains 'fail', the
 *    request fails. For all other values, it succeeds.
 *
 * ### Example
 * <code>
 * // Create a gateway for the Epayco Gateway
 * // (routes to GatewayFactory::create)
 * $gateway = Omnipay::create('Epayco');
 *
 * // Initialise the gateway
 * $gateway->initialize(array(
 *     'testMode' => true, // Doesn't really matter what you use here.
 * ));
 *
 * // Create a credit card object
 * // This card can be used for testing.
 * $card = new CreditCard(array(
 *             'firstName'    => 'Example',
 *             'lastName'     => 'Customer',
 *             'number'       => '4242424242424242',
 *             'expiryMonth'  => '01',
 *             'expiryYear'   => '2020',
 *             'cvv'          => '123',
 * ));
 *
 * // Do a purchase transaction on the gateway
 * $transaction = $gateway->purchase(array(
 *     'amount'                   => '10.00',
 *     'currency'                 => 'AUD',
 *     'card'                     => $card,
 * ));
 * $response = $transaction->send();
 * if ($response->isSuccessful()) {
 *     echo "Purchase transaction was successful!\n";
 *     $sale_id = $response->getTransactionReference();
 *     echo "Transaction reference = " . $sale_id . "\n";
 * }
 * </code>
 */
class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'Epayco';
    }

    public function getDefaultParameters()
    {
        return array(
            'username' => '',
            'pkey' => '',
            'publicKey' => '',
            'lang' => 'es',
            'testMode' => false,
        );
    }

    public function getUsername()
    {
        return $this->getParameter('username');
    }

    public function setUsername($value)
    {
        return $this->setParameter('username', $value);
    }

    public function getPkey()
    {
        return $this->getParameter('pkey');
    }

    public function setPkey($value)
    {
        return $this->setParameter('pkey', $value);
    }

    public function getPrivatekey()
    {
        return $this->getParameter('privatekey');
    }

    public function setPrivatekey($value)
    {
        return $this->setParameter('privatekey', $value);
    }

    public function getPublicKey()
    {
        return $this->getParameter('publicKey');
    }

    public function setPublicKey($value)
    {
        return $this->setParameter('publicKey', $value);
    }

    public function getCheckoutMode()
    {
        return $this->getParameter('checkoutmode');
    }

    public function setCheckoutMode($value)
    {
        return $this->setParameter('checkoutmode', $value);
    }

    public function getLang()
    {
        return $this->getParameter('lang');
    }

    public function setLang($value)
    {
        return $this->setParameter('lang', $value);
    }

    public function getSubTotal()
    {
        return $this->getParameter('subTotal');
    }

    public function setSubTotal($value)
    {
        return $this->setParameter('subTotal', $value);
    }

    public function getTax()
    {
        return $this->getParameter('tax');
    }

    public function setTax($value)
    {
        return $this->setParameter('tax', $value);
    }

    public function getIco()
    {
        return $this->getParameter('ico');
    }

    public function setIco($value)
    {
        return $this->setParameter('ico', $value);
    }

    public function getCountry()
    {
        return $this->getParameter('country');
    }

    public function setCountry($value)
    {
        return $this->setParameter('country', $value);
    }

    public function getHasCvv()
    {
        return $this->getParameter('hascvv');
    }

    public function setHasCvv($value)
    {
        return $this->setParameter('hascvv', $value);
    }

    public function setIpClient($value)
    {
        return $this->setParameter('ipclient', $value);
    }

    public function getIpClient()
    {
        return $this->getParameter('ipclient');
    }

    public function setExtraEpayco($value)
    {
        return $this->setParameter('extraepayco', $value);
    }

    public function getExtraEpayco()
    {
        return $this->getParameter('extraepayco');
    }

    public function setExtras($value)
    {
        return $this->setParameter('extras', $value);
    }

    public function getExtras()
    {
        return $this->getParameter('extras');
    }

    public function setEpaycoPaymentMethodDisable($value)
    {
        return $this->setParameter('epaycopaymentmethoddisable', $value);
    }

    public function getEpaycoPaymentMethodDisable()
    {
        return $this->getParameter('epaycopaymentmethoddisable');
    }


    /**
     * Getter: get cart items.
     *
     * @return array
     */
    public function getCart()
    {
        return $this->getParameter('cart');
    }

    /**
     * Array of cart items.
     *
     * format:
     * $gateway->setCart(array(
     * array(
     * 'type'        => 'product',
     * 'name'        => 'Product 1',
     * 'description' => 'Description of product 1',
     * 'quantity'    => 2,
     * 'price'       => 22,
     * 'tangible'    => 'N',
     * 'product_id'  => 12345,
     * 'recurrence'  => '1 Week',
     * 'duration'    => '1 Year',
     * 'startup_fee' => '5',
     * ),
     * array(
     * 'type'     => 'product',
     * 'name'     => 'Product 2',
     * 'quantity' => 1,
     * 'price'    => 10,
     * 'tangible' => 'N'
     * 'product_id'  => 45678,
     * 'recurrence'  => '2 Week',
     * 'duration'    => '1 Year',
     * 'startup_fee' => '3',
     * )
     * ));
     *
     * @param array $value
     *
     * @return $this
     */
    public function setCart($value)
    {
        return $this->setParameter('cart', $value);
    }

    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Epayco\Message\CreditCardRequest', $parameters);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Epayco\Message\CreditCardRequest', $parameters);
    }

    public function completeAuthorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Epayco\Message\TransactionReferenceRequest', $parameters);
    }

    public function capture(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Epayco\Message\TransactionReferenceRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Epayco\Message\TransactionReferenceRequest', $parameters);
    }

    public function refund(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Epayco\Message\TransactionReferenceRequest', $parameters);
    }

    public function void(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Epayco\Message\TransactionReferenceRequest', $parameters);
    }
}
