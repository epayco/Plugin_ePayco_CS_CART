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

if (defined('PAYMENT_NOTIFICATION')) {

    // states success
    $statusSuccess = array(1, 3);

    // Get the processor data
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);
    $processor_data = fn_get_payment_method_data($payment_id);
    $order_info = fn_get_order_info($_REQUEST['order_id']);

    $pp_response = array();
    $pp_response['order_status'] = (in_array($_REQUEST['x_cod_response'], $statusSuccess)) ? 'P' : 'F';
    $pp_response['reason_text'] = $_REQUEST['x_response_reason_text'];
    $pp_response['transaction_id'] = $_REQUEST['x_transaction_id'];

    fn_update_order_payment_info($_REQUEST['order_id'], $pp_response);
    fn_change_order_status($_REQUEST['order_id'], $pp_response['order_status'], '', false);
    fn_finish_payment($_REQUEST['order_id'], $pp_response);
    fn_order_placement_routines('route', $_REQUEST['order_id']);
    
    exit;

} else {

    $p_tax = 0;
    if($processor_data['processor_params']['p_tax'] != 0) {
        $p_tax = ($processor_data['processor_params']['p_tax'] * $order_info['total']) / 100;
    }

    $p_amount_base = 0;
    if($p_tax != 0) {
        $p_amount_base = $order_info['total'] - $p_tax;
    }

    $p_signature = md5($processor_data['processor_params']['p_cust_id_cliente'].'^'.$processor_data['processor_params']['p_key'].'^'.$order_id.'^'.$order_info['total'].'^'.$order_info['secondary_currency']);


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

    $form_data = array(
        'p_cust_id_cliente' => $processor_data['processor_params']['p_cust_id_cliente'],
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
        'p_signature' => $p_signature,
        'p_confirm_method' => 'POST'
    );

    fn_create_payment_form('https://secure.payco.co/checkout.php', $form_data, 'ePayco', false);
}
exit;