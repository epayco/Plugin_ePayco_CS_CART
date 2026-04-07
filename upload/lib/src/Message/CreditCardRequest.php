<?php
namespace Omnipay\Epayco\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Epayco\Gateway;

/**
 * Epayco Authorize/Purchase Request
 *
 * This is the request that will be called for any transaction which submits a credit card,
 * including `authorize` and `purchase`
 */
class CreditCardRequest extends AbstractRequest
{
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

    public function getPrivateKey()
    {
        return $this->getParameter('privateKey');
    }

    public function setPrivateKey($value)
    {
        return $this->setParameter('privateKey', $value);
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

    public function getFirstName()
    {
        return $this->getParameter('firstName');
    }

    public function setFirstName($value)
    {
        return $this->setParameter('firstName', $value);
    }

    public function getLastName()
    {
        return $this->getParameter('lastName');
    }

    public function setLastName($value)
    {
        return $this->setParameter('lastName', $value);
    }

    public function getEmail()
    {
        return $this->getParameter('email');
    }

    public function setEmail($value)
    {
        return $this->setParameter('email', $value);
    }

    public function getAddress()
    {
        return $this->getParameter('address');
    }

    public function setAddress($value)
    {
        return $this->setParameter('address', $value);
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
     * @param array $value
     *
     * @return $this
     */
    public function setCart($value)
    {
        return $this->setParameter('cart', $value);
    }

    public function getData()
    {
        $this->validate('amount', 'returnUrl', 'notifyUrl');
        $baseData = $this->getBaseData();
        $name_billing = $this->getFirstName() . ' ' . $this->getLastName();
        $myIp = $this->getIpClient() ?: $this->getParameter('ipclient');
        $payload = array(
            "name"=>$this->getDescription(),
            "description"=>$this->getDescription(),
            "invoice"=>(string)$this->getTransactionId(),
            "currency"=>$this->getCurrency(),
            "amount"=>floatval($this->getAmount()),
            "taxBase"=>floatval($this->getSubTotal()),
            "tax"=>floatval($this->getTax()),
            "taxIco"=>floatval($this->getIco()),
            "country"=>$this->getCountry(),
            "lang"=> $this->getLang(),
            "confirmation"=>$this->getNotifyUrl(),
            "response"=>$this->getReturnUrl(),
            "billing" => [
                "name" => $name_billing,
                "address" => $this->getAddress(),
                "email" => $this->getEmail(),
                //"mobilePhone" => $phone_billing
            ],
            "autoclick"=> true,
            "ip"=>$myIp,
            "test"=>$this->getTestMode(),
            "extras" => $this->getExtras(),
            "extrasEpayco" => [
                "extra5" => $this->getExtraEpayco()
            ],
            "epaycoMethodsDisable" => $this->getEpaycoPaymentMethodDisable() ?? [],
            "method"=> "POST",
            "checkout_version"=>"2",
            "autoClick" => false,
            "noRedirectOnClose"=> true,
            "forceResponse"=>false,//mostrar detalle de orden
            "uniqueTransactionPerBill"=> false,
        );
        $formated_data = array_map(function ($value) {
                return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'UTF-8') : $value;
            }, $payload);
        $json = json_encode($formated_data);
        if ($json === false) {
            // json_encode failed; return empty string to satisfy expected string return type
            return '';
        }
        $data['public_key'] = $baseData['publicKey'];
        $data['private_key'] = $baseData['privateKey'];
        $data['checkoutmode'] = $baseData['checkoutmode'];
        $data['testMode'] = $baseData['testmode'];
        $data['payload'] = $formated_data;
        //$data['token'] = base64_encode($json);
        return $data;
    }

    public function sendData($data)
    {
        /*$data['transactionId'] = $data['transactionId'] ?? uniqid();
        $data['success'] = true;
        $data['message'] = $data['success'] ? 'Success' : 'Failure';
        */
        return $this->response = new Response($this, $data);
    }

    protected function getBaseData()
    {
        $data = array();
        $data['user'] = $this->getUsername();
        $data['pkey'] = $this->getPkey();
        $data['publicKey'] = $this->getPublicKey();
        $data['privateKey'] = $this->getPrivateKey();
        $data['lang'] = $this->getLang();
        $data['checkoutmode'] = $this->getCheckoutMode();
        $data['testmode'] = $this->getTestMode();

        return $data;
    }
}
