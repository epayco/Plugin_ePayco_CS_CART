<?php
namespace Omnipay\Epayco\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Epayco Response
 *
 * This is the response class for all Epayco requests.
 *
 * @see \Omnipay\Epayco\Gateway
 */
class Response extends AbstractResponse implements RedirectResponseInterface
{
    protected $endpoint = 'https://eks-cms-backend-platforms-service.epayco.io/omnipay/checkout/payment';

    protected $apify = 'https://eks-apify-service.epayco.io';

    public function isSuccessful()
    {
        return false;
    }

    public function isRedirect()
    {
        return true;
    }

    public function getRedirectUrl()
    {
        return $this->getCheckoutEndpoint().'?'.http_build_query($this->getToken(), '', '&');
    }

    public function getRedirectMethod()
    {
        return 'GET';
    }

    public function getRedirectData()
    {
        return $this->data ?? null;
    }

    public function getTransactionReference()
    {
        return $this->data ?? null;
    }

    public function getToken()
    {
        $publicKey = '';
        $privateKey = '';
        $checkoutmode = '';
        $payload = [];
        $test = false;
        $data = [];
        foreach ($this->getRedirectData() as $key => $value) {

            if($key == 'public_key'){
                $publicKey = $value;
            }
            if($key == 'private_key'){
                $privateKey = $value;
            }  
            
            if($key == 'payload'){
                $payload = $value;  
            }

            if($key == 'checkoutmode'){
                $checkoutmode = $value;
            }

            if($key == 'testMode'){
                $test = $value;
            }
        }

        if($publicKey == '' || $privateKey == ''){
            throw new \InvalidArgumentException('Public key and Private key are required');
        }
        if(empty($payload)){
            throw new \InvalidArgumentException('Payload is required');
        }
        $data['checkoutmode'] = $checkoutmode;
        $data['testMode'] = $test;
        $tokenResponse = $this->getPaymentSessionId($publicKey, $privateKey, $payload);
        $bearerToken = ($tokenResponse && isset($tokenResponse['success'])) ? $tokenResponse['success'] : '';
        if($bearerToken){
            // Use the bearer token for further API calls
            $token_session = $tokenResponse['sessionId'];
            $data['sessionId'] = $token_session;
        }
        return $data;
    }

    protected function getRedirectQueryParameters()
    {
        return $this->getToken();
    }

    public function getTransactionId()
    {
        return $this->data['invoice'] ?? null;
    }

    public function getCardReference()
    {
        return $this->data['invoice'] ?? null;
    }

    public function getMessage()
    {
        return $this->data['message'] ?? null;
    }

    protected function getCheckoutEndpoint()
    {
        return $this->endpoint;
    }

    public function getRedirectResponse()
    {
        $hiddenFields = '';
        foreach ($this->getRedirectData() as $key => $value) {
            $hiddenFields .= sprintf(
                '<input type="hidden" name="%1$s" value="%2$s" />',
                htmlentities($key, ENT_QUOTES, 'UTF-8', false),
                htmlentities((string) $value, ENT_QUOTES, 'UTF-8', false)
            )."\n";
        }
         
        $body = $this->getCheckoutbody();

        $output = '<!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <title>ePayco</title>
            </head>
                %1$s
            </html>';
        $output = sprintf(
            $output,
            $body
        );
        //return new \Omnipay\Common\Http\Response($output);
        return $output;
    }


    public function getCheckoutbody()
    {
        $publicKey = '';
        $privateKey = '';
        $checkoutmode = '';
        $payload = [];
        $hiddenFieldsSSession='';
        $test = false;
        
        foreach ($this->getRedirectData() as $key => $value) {

            if($key == 'payload' && $value['lang']){
                if(strtoupper($value['lang']) == 'ES'){
                    $button = 'https://multimedia-epayco-preprod.s3.us-east-1.amazonaws.com/plugins-sdks/botonPagarEpayco.png';
                }else{
                    $button = 'https://multimedia-epayco-preprod.s3.us-east-1.amazonaws.com/plugins-sdks/payBottonEpayco.png';  
                }
            }

            if($key == 'public_key'){
                $publicKey = $value;
            }
            if($key == 'private_key'){
                $privateKey = $value;
            }  
            
            if($key == 'payload'){
                $payload = $value;  
            }

            if($key == 'checkoutmode'){
                $checkoutmode = $value;
            }

            if($key == 'testMode'){
                $test = $value;
            }
        }

        if($publicKey == '' || $privateKey == ''){
            throw new \InvalidArgumentException('Public key and Private key are required');
        }
        if(empty($payload)){
            throw new \InvalidArgumentException('Payload is required');
        }

        $tokenResponse = $this->getPaymentSessionId($publicKey, $privateKey, $payload);
        $bearerToken = ($tokenResponse && isset($tokenResponse['success'])) ? $tokenResponse['success'] : '';
        if($bearerToken){
            // Use the bearer token for further API calls
            $token_session = $tokenResponse['sessionId'];
            $hiddenFieldsSSession .= sprintf(
                '<input type="hidden" name="%1$s" value="%2$s" />',
                htmlentities('token_session', ENT_QUOTES, 'UTF-8', false),
                htmlentities((string) $token_session, ENT_QUOTES, 'UTF-8', false)
            )."\n";
                $body = '
                <body>      
                    <div>
                        <a id="btn_epayco" href="#">
                            <img src="'.$button.'" alt="ePayco" style="width: 290px !important;" />
                        </a>
                    </div> 
                    <script src="https://epayco-checkout-testing.s3.amazonaws.com/checkout.preprod-v2.js"></script>
                    <script>
                    let testMode = %3$s ? true : false;
                    const checkout = ePayco.checkout.configure({
                        sessionId: "%1$s",
                        type: "%2$s",
                        test: testMode
                    });
                    var bntPagar = document.getElementById("btn_epayco");
                    var openNewChekout = function () {
                        checkout.open();
                    }      
                    var openChekout = function () {
                        //bntPagar.style.pointerEvents = "none";
                        //bntPagar.style.opacity = "0.5";
                        openNewChekout();
                    }
                    bntPagar.addEventListener("click", openChekout);
                    setTimeout(function() {
                        openChekout();
                    }, 2000);
                </script>
                </body>';

            $body = sprintf(
                $body,
                (string) $token_session,
                $checkoutmode,
                $test ? 'true' : 'false'
            );
            return $body;
        }else{
            $errorMessage = $tokenResponse && isset($tokenResponse['message']) ? $tokenResponse['message'] : 'Error generating authentication token.';
            return $this->errorResponse($errorMessage);
        }
    }

    public function errorResponse($message){
        $body = '
            <body>      
                <div style="
                    display: flex;
                    align-items: center;
                    flex-direction: column;
                ">
                <div>
                <img style="width: 80px;" src="https://multimedia-epayco-preprod.s3.us-east-1.amazonaws.com/plugins-sdks/warning.png" alt="" />
                </div>
                <div 
                style="text-align: center;font-size: large;font-weight: 900;">
                    <p class="warning">
                       <p> %1$s </p>
                       <p> %2$s </p>
                    </p>
                </div>
            </div>
            </body>';

            $body = sprintf(
                $body,
                'Hemos notado un problema con tu orden, solicitamos contactar a nuestro departamento de Soporte',
                $message
            );
        return $body;
    }

    public function epyacoBerarToken($publicKey,$privateKey)
    {
        if (!isset($_COOKIE[$publicKey])) {
            $token = base64_encode($publicKey . ":" . $privateKey);
            $bearer_token = $token;
            $cookie_value = $bearer_token;
            setcookie($publicKey, $cookie_value, time() + (60 * 14), "/");
        } else {
            $bearer_token = $_COOKIE[$publicKey];
        }
        $headers = "Authorization: Basic {$bearer_token}";
        return $this->apiService("login", [],'POST', $headers);
    }

    public function getPaymentSessionId($publicKey, $privateKey, $payload)
    {
        $tokenResponse = $this->epyacoBerarToken($publicKey, $privateKey);
        $bearerToken = ($tokenResponse && isset($tokenResponse->token)) ? $tokenResponse->token : '';
        if(!$bearerToken){
            return $this->formatErrorMessage($tokenResponse);
        }
        $path = "payment/session/create";
        $headers = "Authorization: Bearer {$bearerToken}";
        $epayco_status_session =  $this->apiService($path, $payload,'POST', $headers);

        if ($epayco_status_session && isset($epayco_status_session->success) && $epayco_status_session->success) {
            $token_session = $epayco_status_session->data->sessionId;
            return [
                "success"=>true,
                "sessionId"=>$token_session,
            ];
        } else {
            return $this->formatErrorMessage($epayco_status_session);
        }
    }

    private function formatErrorMessage($epayco_status_session){
        $messageError = $epayco_status_session->textResponse;
        $errorMessage = "";
        if (isset($epayco_status_session->data->errors)) {
            $errors = $epayco_status_session->data->errors;
            if(is_array($errors)){
                foreach ($errors as $error) {
                    $errorMessage = $error->errorMessage . "\n";
                }
            }else{
                $errorMessage = $errors. "\n";
            }
        } elseif (isset($epayco_status_session->data->error->errores)) {
            $errores = $epayco_status_session->data->error->errores;
            foreach ($errores as $error) {
                $errorMessage = $error['errorMessage'] . "\n";
            }
        }elseif(isset($epayco_status_session->error)){
            $errorMessage = $epayco_status_session->error . "\n";
        }
        //$processReturnFailMessage = $messageError . " " . $errorMessage;
        $processReturnFailMessage =  $errorMessage;
        return [
            "success"=>false,
            "message"=>$processReturnFailMessage,
        ];
    }

    public function apiService($url, $data, $type, $cabecera = null)
    {
        
        $header = [
            "Cache-Control: no-cache",
            "Accept: application/json",
            "Content-Type: application/json",
        ];

        try {
            if ($cabecera) {
                if (is_array($cabecera)) {
                    $header = array_merge($header, $cabecera);
                } else {
                    $header[] = $cabecera;
                }
            }
            $bvaseUrl = $this->apify;
            $url = "{$bvaseUrl}/{$url}";
            $jsonData = json_encode($data);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSLKEYPASSWD => '',
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 600,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST => $type,
                CURLOPT_POSTFIELDS => $jsonData,
            ));
            $resp = curl_exec($curl);
            if ($resp === false) {
                return array('curl_error' => curl_error($curl), 'curerrno' => curl_errno($curl));
            }
            curl_close($curl);
            return json_decode($resp);
        } catch (\Exception $exception) {
            return [
                "success" => false,
                "titleResponse" => "error",
                "textResponse" => $exception->getMessage(),
                "data" => []
            ];
        }
    }

    
}
