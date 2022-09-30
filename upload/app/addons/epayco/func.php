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

use Tygh\Registry;
use Tygh\Settings;
use Tygh\Http;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_epayco_uninstall_payment_processors() 
{
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('epayco.php')))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('epayco.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('epayco.php')");
}

function fn_epayco_uninstall_pages_processors()
{
    db_query("DELETE FROM ?:pages WHERE page_id IN ('201')");
}

function fn_epayco_uninstall_page_descriptions_processors()
{
    db_query("DELETE FROM ?:page_descriptions WHERE page_id IN ('201')");
}

/**
 * Hook handler: clears the cart in the session if IPN for placed orders is already received.
 *
 * @param array $auth       Current user session data
 * @param array $user_info  User infromation obtained from ::fn_get_user_short_info
 * @param bool  $first_init True if stored in session data used to log in the user
 */
function fn_epayco_user_init(&$auth, &$user_info, &$first_init)
{
    $orders_list = array();
    if (!empty(Tygh::$app['session']['cart']['processed_order_id'])) {
        $orders_list = array_merge($orders_list, (array)Tygh::$app['session']['cart']['processed_order_id']);
    }
    if (!empty(Tygh::$app['session']['cart']['failed_order_id'])) {
        $orders_list = array_merge($orders_list, (array)Tygh::$app['session']['cart']['failed_order_id']);
    }
    foreach ($orders_list as $order_id) {
        if (fn_is_epayco_ipn_received($order_id)) {
            fn_clear_cart(Tygh::$app['session']['cart']);
            die(fn_epayco_order_total_is_correct($order_id));
            break;
        }
    }
}


/**
 * Checks if Epayco IPN for the order is received by searching for the IPN receiving time
 * in the order's payment information.
 *
 * @param int $order_id The identifier of the order.
 *
 * @return bool True if IPN was received
 */
function fn_is_epayco_ipn_received($order_id)
{
    $order_info = fn_get_order_info($order_id);

    return $order_info['payment_method']['payment'] == "ePayco";
}

/**
 * Checks the total of the specified order against the session's order total to make sure
 * that the order was placed properly.
 *
 * @param int $order_id The identifier of the order.
 *
 * @return bool True if the order total is correct and matches the session's order total; false otherwise.
 */
function fn_epayco_order_total_is_correct($order_id)
{
    $order_info = fn_get_order_info($order_id);
    $tax = floatval($order_info['total']) - $order_info['subtotal'];
    $formattedData = array(
        'key' =>  $order_info['payment_method']['processor_params']['p_public_key'],
        'test' => $order_info['payment_method']['processor_params']['p_test_request'],
        'order_id' => $order_id,
        'currency' => $order_info['secondary_currency'],
        'total' => $order_info['total'],
        'tax' => $tax,
        'sub_total' => $order_info['subtotal'],
        'country' => $order_info["b_country"],
        'external' => 'false',
        'lang' => $order_info["lang_code"]
    );
    $queryParams = http_build_query($formattedData);
    $id_page = "201";
    $url_checkout = fn_url("pages.view&page_id=".$id_page."&");

    $description = db_get_field("SELECT description FROM ?:page_descriptions WHERE page_id = ?i", $id_page);
    if(count($description) > 0){
        header('Location: '.$url_checkout.$queryParams);
    }
    return true;
}

function fn_epayco_prepare_checkout_payment_methods(&$cart, &$auth, &$payment_groups)
{
    if (isset($cart['payment_id'])) {
        foreach ($payment_groups as $tab => $payments) {
            foreach ($payments as $payment_id => $payment_data) {
                if (isset(Tygh::$app['session']['pp_epayco_details'])) {
                    if ($payment_id != $cart['payment_id']) {
                        unset($payment_groups[$tab][$payment_id]);
                    } else {
                        $_tab = $tab;
                    }
                }
            }
        }
        if (isset($_tab)) {
            $_payment_groups = $payment_groups[$_tab];
            $payment_groups = array();
            $payment_groups[$_tab] = $_payment_groups;
        }
    }
}

/**
 * Overrides user existence check results for guest customers who returned from Express Checkout
 *
 * @param int $user_id User ID
 * @param array $user_data User authentication data
 * @param boolean $is_exist True if user with specified email already exists
 */
function fn_epayco_is_user_exists_post($user_id, $user_data, &$is_exist)
{
    if (!$user_id && $is_exist) {
        if (isset(Tygh::$app['session']['pp_epayco_details']['token']) &&
            (empty($user_data['register_at_checkout']) || $user_data['register_at_checkout'] != 'Y') &&
            empty($user_data['password1']) && empty($user_data['password2'])) {
            $is_exist = false;
        }
    }
    $orders_list = array();
    if (!empty(Tygh::$app['session']['cart']['processed_order_id'])) {
        $order_id = array_merge($orders_list, (array)Tygh::$app['session']['cart']['processed_order_id']);
        fn_epayco_order_total_is_correct($order_id[0]);
    }

}

/**
 * Provide token and handle errors for checkout with In-Context checkout
 *
 * @param array $cart   Cart data
 * @param array $auth   Authentication data
 * @param array $params Request parameters
 */
function fn_epayco_checkout_place_orders_pre_route(&$cart, $auth, $params)
{
    $cart = empty($cart) ? array() : $cart;
    $payment_id = (empty($params['payment_id']) ? $cart['payment_id'] : $params['payment_id']);
    $processor_data = fn_get_processor_data($payment_id);

    if (!empty($processor_data['processor_script']) && $processor_data['processor_script'] == 'epayco.php' &&
        isset($params['in_context_order']) && $processor_data['processor_params']['in_context'] == 'Y'
    ) {
        // parent order has the smallest identifier of all the processed orders
        $order_id = min($cart['processed_order_id']);
        Tygh::$app['ajax']->assign('token', $order_id);
        exit;
    }
}
