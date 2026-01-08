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

use Tygh\Enum\ImagePairTypes;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Providers\StorefrontProvider;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


function fn_epayco_delete_payment_processors()
{
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('epayco.php', 'epayco_pro.php', 'payflow_pro.php', 'epayco.php', 'epayco_advanced.php')))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('epayco.php', 'epayco_pro.php', 'payflow_pro.php', 'epayco.php', 'epayco_advanced.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('epayco.php', 'epayco_pro.php', 'payflow_pro.php', 'epayco.php', 'epayco_advanced.php')");
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
            // Removed fn_epayco_order_total_is_correct() call - Omnipay handles redirects
            break;
        }
    }
}


/**
 * Checks the total of the specified order against the session's order total to make sure
 * that the order was placed properly.
 *
 * @param int $order_id The identifier of the order.
 *
 * @return bool True if the order total is correct and matches the session's order total; false otherwise.
 * 
 * @deprecated This function is no longer used. Omnipay handles payment redirects automatically.
 */
function fn_epayco_order_total_is_correct($order_id)
{
    // This function has been deprecated.
    // Payment redirects are now handled by Omnipay in epayco.php
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
        // Removed fn_epayco_order_total_is_correct() call - Omnipay handles redirects
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
 * Updates add-on settings.
 *
 * @param array<string, string|array> $settings      Add-on settings
 * @param int|null                    $storefront_id Storefront ID to set settings for
 *
 * @psalm-param array{
 *   pp_logo_update_all_storefronts?: string,
 *   pp_statuses?: array<string>|string,
 * } $settings
 *
 * @internal
 */
function fn_update_epayco_settings(array $settings, $storefront_id = null)
{
    if (isset($settings['pp_statuses'])) {
        $settings['pp_statuses'] = serialize($settings['pp_statuses']);
    }

    $settings_manager = Settings::instance(['storefront_id' => $storefront_id]);
    foreach ($settings as $setting_name => $setting_value) {
        $settings_manager->updateValue($setting_name, $setting_value);
    }

    if (
        isset($settings['pp_logo_update_all_storefronts'])
        && YesNo::toBool($settings['pp_logo_update_all_storefronts'])
    ) {
        list($storefronts,) = StorefrontProvider::getRepository()->find();
        foreach ($storefronts as $storefront) {
            fn_delete_image_pairs($storefront->storefront_id, 'epayco_logo');
        }
    }

    fn_attach_image_pairs('epayco_logo', 'epayco_logo', (int) $storefront_id);
}

/**
 * Gets add-on settings.
 *
 * @param int|null $storefront_id Storefront to get settings for
 *
 * @return array<string, string|array>
 *
 * @psalm-return array{
 *   main_pair: array{
 *     pair_id: int,
 *     object_id: int,
 *     detailed: array{
 *       object_id: int,
 *     },
 *   }|array<empty, empty>,
 *   pp_statuses: array<string, string>,
 *   partial_refund_action: string,
 *   override_customer_info: string,
 * }
 *
 * @internal
 */
function fn_get_epayco_settings($storefront_id = null)
{
    /**
     * @psalm-var array{
     *   main_pair: array{
     *     pair_id: int,
     *     object_id: int,
     *     detailed: array{
     *       object_id: int,
     *     },
     *   },
     *   pp_statuses: string,
     *   partial_refund_action: string,
     *   override_customer_info: string,
     * } $pp_settings
     */
    $pp_settings = Settings::instance()->getValues('epayco', 'ADDON', false);
    if (!empty($pp_settings['pp_statuses'])) {
        $pp_settings['pp_statuses'] = unserialize($pp_settings['pp_statuses']);
    } else {
        $pp_settings['pp_statuses'] = [];
    }

    if (!$storefront_id && SiteArea::isStorefront(AREA)) {
        $storefront_id = StorefrontProvider::getStorefront()->storefront_id;
    }

    $pp_settings['main_pair'] = fn_get_image_pairs((int) $storefront_id, 'epayco_logo', ImagePairTypes::MAIN, false, true);
    if (!$pp_settings['main_pair']) {
        $fallback_logo = fn_get_image_pairs(0, 'epayco_logo', 'M', false, true);
        if ($fallback_logo) {
            $fallback_logo['pair_id'] = 0;
            $fallback_logo['object_id'] = $storefront_id;
            $fallback_logo['detailed']['object_id'] = $storefront_id;
        }
        $pp_settings['main_pair'] = $fallback_logo;
    }

    /**
     * @psalm-var array{
     *   main_pair: array{
     *     pair_id: int,
     *     object_id: int,
     *     detailed: array{
     *       object_id: int,
     *     },
     *   }|array<empty, empty>,
     *   pp_statuses: array<string, string>,
     *   partial_refund_action: string,
     *   override_customer_info: string,
     * } $pp_settings
     */

    return $pp_settings;
}




function fn_pp_save_mode($order_info)
{
    $data['pp_mode'] = 'test';
    if (!empty($order_info['payment_method']) && !empty($order_info['payment_method']['processor_params']) && !empty($order_info['payment_method']['processor_params']['mode'])) {
        $data['pp_mode'] = $order_info['payment_method']['processor_params']['mode'];
    }
    fn_update_order_payment_info($order_info['order_id'], $data);

    return true;
}

/**
 * Checks if payment processor is the one provided by the add-on.
 *
 * @param int $processor_id
 *
 * @return bool True if processor is epayco-based
 */
function fn_is_epayco_processor($processor_id = 0)
{
    return (bool) db_get_field("SELECT 1 FROM ?:payment_processors WHERE processor_id = ?i AND addon = ?s", $processor_id, 'epayco');
}




