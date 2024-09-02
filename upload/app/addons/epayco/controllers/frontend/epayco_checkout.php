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

if (!defined('BOOTSTRAP')) {
    require './../../../../payments/init_payment.php';
}
use Tygh\Registry;

function getCustomerIp(){
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
function fn_epayco_checkout_action()
{
    $view = Tygh::$app['view'];
    $order_id = $_REQUEST['order_id'];
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $processor_data = fn_get_payment_method_data($payment_id);
    $order_info = fn_get_order_info($order_id);
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

    /** @var \Tygh\Location\Manager $location_manager */
    $location_manager = Tygh::$app['location'];
    $type_checkout = $order_info['payment_method']['processor_params']['p_type_checkout'];
    if($type_checkout == "TRUE"){
        $type_checkout_mode = "true";
    }else{
        $type_checkout_mode = "false";
    }
    $lang = $order_info["lang_code"];
    if ($lang!== "es") {
        $msgEpaycoCheckout = 'Loading payment methods';
        $msgEpaycoCheckoutDescription = 'If they do not load automatically, click on the Pay with ePayco button';
        $epaycoButtonImage = 'https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color1-Ingles.png';
    }else{
        $msgEpaycoCheckout = 'Cargando métodos de pago';
        $msgEpaycoCheckoutDescription = 'Si no se cargan automáticamente, de clic en el botón Pagar con ePayco';
        $epaycoButtonImage =  'https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color1.png';
    }
    $ip = getCustomerIp();
    $view->assign('order_id', $order_id);
    $view->assign('p_cust_id_cliente', $processor_data['processor_params']['p_cust_id_cliente']);
    $view->assign('p_public_key', $processor_data['processor_params']['p_public_key']);
    $view->assign('p_private_key', $processor_data['processor_params']['p_private_key']);
    $view->assign('p_description', $p_description);
    $view->assign('currency_code', $order_info['secondary_currency']);
    $view->assign('amount', $order_info['total']);
    $view->assign('tax', $p_tax);
    $view->assign('amount_base', $p_amount_base);
    $view->assign('test_request', strtolower($processor_data['processor_params']['p_test_request']));
    $view->assign('url_response', $p_url_response);
    $view->assign('url_confirmation',  $p_url_confirmation);
    $view->assign('billAddress', $location_manager->getLocationField($order_info, 'address', '', BILLING_ADDRESS_PREFIX));
    $view->assign('shipCountry', $location_manager->getLocationField($order_info, 'country', '', SHIPPING_ADDRESS_PREFIX));
    $view->assign('payerEmail', $order_info['email']);
    $view->assign('payerPhone', $location_manager->getLocationField($order_info, 'phone', '', SHIPPING_ADDRESS_PREFIX));
    $view->assign('type_checkout_mode', $type_checkout_mode);
    $view->assign('lang', $lang);
    $view->assign('msgEpaycoCheckout', $msgEpaycoCheckout);
    $view->assign('msgEpaycoCheckoutDescription', $msgEpaycoCheckoutDescription);
    $view->assign('url_button', $epaycoButtonImage);
    $view->assign('ip', $ip);
}

if ($mode == 'epayco') {
    fn_epayco_checkout_action();
}

