<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

if (!defined('BOOTSTRAP')) { die('Access denied'); }
use Tygh\Tygh;
if (defined('PAYMENT_NOTIFICATION')) {

    $confirmation = false;
    $ref_payco = $_GET['ref_payco'];
    if(empty($ref_payco)){
        $validationData = $_REQUEST;
        $confirmation = true;
    }else{
        $url = 'https://secure.epayco.co/validation/v1/reference/'.$ref_payco;
        $responseData =  @file_get_contents($url);
        $jsonData = @json_decode($responseData, true);
        $validationData = $jsonData['data'];
    }

    if(!empty($validationData)){
        $x_signature = trim($validationData['x_signature']);
        $x_cod_transaction_state = (int)trim($validationData['x_cod_transaction_state']);
        $x_ref_payco = trim($validationData['x_ref_payco']);
        $x_transaction_id = trim($validationData['x_transaction_id']);
        $x_amount = trim($validationData['x_amount']);
        $x_currency_code = trim($validationData['x_currency_code']);
        $x_test_request = trim($validationData['x_test_request']);
        $x_approval_code = trim($validationData['x_extra1']);
        $x_franchise = trim($validationData['x_franchise']);
        $order_id_ = trim($validationData['x_extra1']);
    }else{
        $order_id_ = null;
    }
    

    // states success
    $statusSuccess = array(1, 3);
    $order_id = !empty($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : (int)$order_id_;
    // Get the processor data
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $processor_data = fn_get_payment_method_data($payment_id);
    
    $order_info = fn_get_order_info($order_id);
    $pp_response = array();

    if(!empty($order_id) && !empty($validationData)){
        $signature = hash('sha256',
            trim($processor_data['processor_params']['p_cust_id_cliente']).'^'
            .trim($processor_data['processor_params']['p_key']).'^'
            .$x_ref_payco.'^'
            .$x_transaction_id.'^'
            .$x_amount.'^'
            .$x_currency_code
        ); 
        $isTestTransaction = $x_test_request == 'TRUE' ? "yes" : "no";
        $isTestMode = $isTestTransaction == "yes" ? "true" : "false";
        $isTestPluginMode = $processor_data['processor_params']['p_test_request']  == 'TRUE' ? "yes" : "no";

        if(floatval($order_info['total']) == floatval($x_amount)){
            if("yes" == $isTestPluginMode){
                $validation = true;
            }
            if("no" == $isTestPluginMode ){
                if($x_approval_code != "000000" && $x_cod_transaction_state == 1){
                    $validation = true;
                }else{
                    if($x_cod_transaction_state != 1){
                        $validation = true;
                    }else{
                        $validation = false;
                    }
                }
                
            }
        }else{
             $validation = false;
        }

        if($signature == $x_signature && $validation){
            switch ($x_cod_transaction_state) {
                case 1: {
                    $pp_response['order_status'] = 'C';
                } break;
                case 2: {
                    $pp_response['order_status'] = 'D';
                } break;
                case 3: {
                    $pp_response['order_status'] = 'Y';
                } break;
                case 4: {
                    $pp_response['order_status'] = 'I';
                } break;
                case 6: {
                    $pp_response['order_status'] = 'I';
                } break;
                case 10:{
                    $pp_response['order_status'] = 'I';
                } break;
                case 11:{
                    $pp_response['order_status'] = 'I';
                } break;
                default: {
                    $pp_response['order_status'] = 'O';
                } break;
            }

            $pp_response['reason_text'] = $_REQUEST['x_response_reason_text'];
            $pp_response['transaction_id'] = $_REQUEST['x_transaction_id'];
            if (fn_check_payment_script('epayco.php', $order_id)) {
                fn_update_order_payment_info($order_id, $pp_response);
                fn_change_order_status($order_id, $pp_response['order_status'], '', false);
            }
        }else{
            $pp_response['order_status'] = 'F';
	        $pp_response['reason_text'] = __('text_transaction_declined');
	        if (fn_check_payment_script('epayco.php', $order_id)) {
                fn_update_order_payment_info($order_id, $pp_response);
                fn_change_order_status($order_id, $pp_response['order_status'], '', false);
            }
        }

    }else{
	    $pp_response['order_status'] = 'F';
	    $pp_response['reason_text'] = __('text_transaction_declined');
	    if (fn_check_payment_script('epayco.php', $order_id)) {
                fn_update_order_payment_info($order_id, $pp_response);
                fn_change_order_status($order_id, $pp_response['order_status'], '', false);
            }
    }
        fn_finish_payment($order_id, $pp_response);
        if($confirmation){
            echo "code response: ".$x_cod_transaction_state;
        }else{
            fn_order_placement_routines('route', $order_id);
        }
        
    exit;
} else {

    $p_tax = 0;
    $indice =array_keys($order_info["taxes"]);
    if($order_info["taxes"][$indice[0]]["tax_subtotal"] != 0) {
        $p_tax = $order_info["taxes"][$indice[0]]["tax_subtotal"];
    }

    $p_amount_base = 0;
    if($p_tax != 0) {
        $p_amount_base = $order_info['total'] - $p_tax;
    }


    $i = 0;
    $p_description = ""; 
    foreach ($order_info['products'] as $k => $v) {
        $i++;
        $p_description .= $v['product'];

        if($i != count($order_info['products'])) {
            $p_description .= "; ";         
        }
    }

    $p_url_response = fn_url("payment_notification.response?payment=epayco&order_id=$order_id", AREA, 'current');
    $p_url_confirmation = fn_url("payment_notification.confirmation?payment=epayco&order_id=$order_id", AREA, 'current');
    $url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    $server_name = str_replace('/confirmation/epayco/index','/checkout/onepage/success/',$url);
    $new_url = $server_name;

    /** @var \Tygh\Location\Manager $location_manager */
    $location_manager = Tygh::$app['location'];

    $form_data = array(
        'p_cust_id_cliente' => $processor_data['processor_params']['p_cust_id_cliente'],
        'p_public_key' => $processor_data['processor_params']['p_public_key'],
        'p_key' => $processor_data['processor_params']['p_key'],    
        'p_id_invoice' => $order_id,
        'p_description' => $p_description,
        'p_currency_code' => $order_info['secondary_currency'],
        'p_amount' => $order_info['total'],
        'p_tax' => $p_tax,
        'p_amount_base' => $p_amount_base,
        'p_test_request' => $processor_data['processor_params']['p_test_request'],
        'p_url_response' => $p_url_response,
        'p_url_confirmation' => $p_url_confirmation,
        'p_confirm_method' => 'POST',
        'url' => $url,
        'dir_file' => dirname(__FILE__),
        'billAddress' => $location_manager->getLocationField($order_info, 'address', '', BILLING_ADDRESS_PREFIX),
        'shipCountry' => $location_manager->getLocationField($order_info, 'country', '', SHIPPING_ADDRESS_PREFIX),
        'payerEmail'  => $order_info['email'],
        'payerPhone'  => $location_manager->getLocationField($order_info, 'phone', '', BILLING_ADDRESS_PREFIX),
    );
    $epaycoButtonImage ="https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color1.png";
    
    echo sprintf('
        <style>
            .loader-container{
                position: relative;
                padding: 20px;
                color: #ff5700;
            }
            .epayco-subtitle{
                font-size: 14px;
            }
            .epayco-button-render{
                transition: all 500ms cubic-bezier(0.000, 0.445, 0.150, 1.025);
                transform: scale(1.1);
                box-shadow: 0 0 4px rgba(0,0,0,0);
            }
            .epayco-button-render:hover {
                /*box-shadow: 0 0 4px rgba(0,0,0,.5);*/
                transform: scale(1.2);
            }
           
        </style>
            <div class="loader-container">
                <div class="loading"></div>
            </div>
            <p style="text-align: center;" class="epayco-title">
            </p> 
            <center>
                <form id="appGateway">
                    <script
                        src="https://checkout.epayco.co/checkout.js"
                        class="epayco-button"
                        data-epayco-key="%s"
                        data-epayco-test="%s"
                        data-epayco-name="%s"
                        data-epayco-description="%s"
                        data-epayco-invoice="%s"      
                        data-epayco-currency="%s"                   
                        data-epayco-amount="%s"
                        data-epayco-tax="%s"
                        data-epayco-tax-base="%s"
                        data-epayco-country="%s"
                        data-epayco-external="%s"                       
                        data-epayco-response="%s"
                        data-epayco-confirmation="%s"
                        data-epayco-lang="%s"
                        data-epayco-button="'.$epaycoButtonImage.'"
                        data-epayco-autoclick="true"
                        data-epayco-extra1="%s"
                        data-epayco-email-billing="%s"
                        data-epayco-address-billing="%s"
                        >
                    </script>
                    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
                    <script>
                        window.onload = function() {
                            document.addEventListener("contextmenu", function(e){
                                e.preventDefault();
                            }, false);
                        } 
                        $(document).keydown(function (event) {
                            if (event.keyCode == 123) {
                                return false;
                            } else if (event.ctrlKey && event.shiftKey && event.keyCode == 73) {        
                                return false;
                            }
                        });
                    </script>
                </form>
                
            </center>
            ',$processor_data['processor_params']['p_public_key'],$processor_data['processor_params']['p_test_request'],
                $p_description,$p_description,$order_id,$order_info['secondary_currency'],$order_info['total'], $p_tax,
                $p_amount_base, $form_data["shipCountry"], "true", $p_url_response, $p_url_confirmation,"es",$order_id,
                $form_data["payerEmail"], $form_data["billAddress"]
        );
}
exit;
