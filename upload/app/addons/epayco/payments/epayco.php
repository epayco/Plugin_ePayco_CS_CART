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

/**
 * @var array $processor_data
 * @var array $order_info
 * @var string $mode
 */

use Tygh\Enum\OrderStatuses;
use Tygh\Languages\Languages;
use Tygh\Tygh;

// Load Omnipay from epayco addon
$omnipay_path = dirname(__FILE__) . '/../lib/src/';
require_once $omnipay_path . 'vendor/autoload.php';

use Omnipay\Omnipay;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

// Detect if it's a payment notification by dispatch or PAYMENT_NOTIFICATION
$is_payment_notification = defined('PAYMENT_NOTIFICATION') ||
    (isset($_REQUEST['dispatch']) && strpos($_REQUEST['dispatch'], 'payment_notification') === 0) ||
    (!empty($_POST) && !empty($_REQUEST['order_id']));

if ($is_payment_notification) {
    // Get the processor data
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);
    $processor_data = fn_get_payment_method_data($payment_id);
    $order_info = fn_get_order_info($_REQUEST['order_id']);

    $confirmation = false;
    $ref_payco = isset($_GET['ref_payco']) ? $_GET['ref_payco'] : '';

    if (empty($ref_payco)) {
        // First call - direct webhook with POST data
        $validationData = $_REQUEST;
        $confirmation = true;
    } else {
        // ePayco redirect - validate against ePayco server
        $url = 'https://secure.epayco.co/validation/v1/reference/' . $ref_payco;
        $responseData = @file_get_contents($url);

        if ($responseData) {
            $jsonData = @json_decode($responseData, true);
            $validationData = isset($jsonData['data']) ? $jsonData['data'] : array();
        } else {
            // If unable to validate with server, use POST data if available
            $validationData = !empty($_REQUEST) ? $_REQUEST : array();
        }
    }

    if (!empty($validationData)) {
        $x_signature = trim($validationData['x_signature'] ?? '');
        $x_cod_transaction_state = (int)trim($validationData['x_cod_transaction_state'] ?? '0');
        $x_ref_payco = trim($validationData['x_ref_payco'] ?? '');
        $x_transaction_id = trim($validationData['x_transaction_id'] ?? '');
        $x_amount = trim($validationData['x_amount'] ?? '0');
        $x_currency_code = trim($validationData['x_currency_code'] ?? '');
        $x_test_request = trim($validationData['x_test_request'] ?? '');
        $x_approval_code = trim($validationData['x_approval_code'] ?? trim($validationData['x_extra1'] ?? ''));
        $x_franchise = trim($validationData['x_franchise'] ?? '');
        $order_id_ = trim($validationData['x_id_invoice'] ?? trim($validationData['x_extra1'] ?? ''));
    } else {
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

    if (!empty($order_id) && !empty($validationData)) {
        $signature = hash(
            'sha256',
            trim($processor_data['processor_params']['p_cust_id_cliente']) . '^'
                . trim($processor_data['processor_params']['p_key']) . '^'
                . $x_ref_payco . '^'
                . $x_transaction_id . '^'
                . $x_amount . '^'
                . $x_currency_code
        );

        $isTestTransaction = $x_test_request == 'TRUE' ? "yes" : "no";
        $isTestMode = $isTestTransaction == "yes" ? "true" : "false";
        $isTestPluginMode = $processor_data['processor_params']['p_test_request']  == 'TRUE' ? "yes" : "no";

        if (floatval($order_info['total']) == floatval($x_amount)) {
            if ("yes" == $isTestPluginMode) {
                $validation = true;
            }
            if ("no" == $isTestPluginMode) {
                if ($x_approval_code != "000000" && $x_cod_transaction_state == 1) {
                    $validation = true;
                } else {
                    if ($x_cod_transaction_state != 1) {
                        $validation = true;
                    } else {
                        $validation = false;
                    }
                }
            }
        } else {
            $validation = false;
        }

        // In TEST mode, allows validation without signature if x_test_request is TRUE
        $allow_without_signature = ($processor_data['processor_params']['p_test_request'] == 'TRUE' && $x_test_request == 'TRUE');

        if (($signature == $x_signature || $allow_without_signature) && $validation) {
            error_log('EPAYCO: Payment validated - Order ID: ' . $order_id . ', Status: ' . $x_cod_transaction_state);
            switch ($x_cod_transaction_state) {
                case 1: {
                        $pp_response['order_status'] = 'C';
                    }
                    break;
                case 2: {
                        $pp_response['order_status'] = 'D';
                    }
                    break;
                case 3: {
                        $pp_response['order_status'] = 'B';
                    }
                    break;
                case 4: {
                        $pp_response['order_status'] = 'I';
                    }
                    break;
                case 6: {
                        $pp_response['order_status'] = 'I';
                    }
                    break;
                case 10: {
                        $pp_response['order_status'] = 'I';
                    }
                    break;
                case 11: {
                        $pp_response['order_status'] = 'I';
                    }
                    break;
                default: {
                        $pp_response['order_status'] = 'O';
                    }
                    break;
            }

            $pp_response['reason_text'] = $validationData['x_response_reason_text'] ?? 'Payment processed';
            $pp_response['transaction_id'] = $validationData['x_transaction_id'] ?? '';
            if (fn_check_payment_script('epayco.php', $order_id)) {
                fn_update_order_payment_info($order_id, $pp_response);
                fn_change_order_status($order_id, $pp_response['order_status'], '', false);
            }
        } else {
            error_log('EPAYCO: Validación fallida - Order ID: ' . $order_id . ', Firma: ' . $x_signature);
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = __('text_transaction_declined');
            if (fn_check_payment_script('epayco.php', $order_id)) {
                fn_update_order_payment_info($order_id, $pp_response);
                fn_change_order_status($order_id, $pp_response['order_status'], '', false);
            }
        }
    } else {
        error_log('EPAYCO: Validation error - order_id empty or data unavailable');
        $pp_response['order_status'] = 'F';
        $pp_response['reason_text'] = __('text_transaction_declined');
        if (fn_check_payment_script('epayco.php', $order_id)) {
            fn_update_order_payment_info($order_id, $pp_response);
            fn_change_order_status($order_id, $pp_response['order_status'], '', false);
        }
    }
    fn_finish_payment($order_id, $pp_response);

    // Always execute order placement routines
    fn_order_placement_routines('route', $order_id);

    // If it was a webhook confirmation, show the code
    if ($confirmation) {
        // Webhook response
        error_log('EPAYCO: Webhook processed - Order ID: ' . $order_id . ', Status: ' . $pp_response['order_status']);
    } else {
        // Redirect to CS-Cart thank you page
        // Build absolute URL with protocol, domain and correct path
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];

        // Get base project path (e.g. /Esteban/cscart3/ or just /)
        $script_path = dirname($_SERVER['SCRIPT_NAME']); // Gets the folder where index.php is located
        if ($script_path !== '/' && $script_path !== '\\') {
            $script_path = rtrim($script_path, '/') . '/';
        } else {
            $script_path = '/';
        }

        $base_url = $protocol . $host . $script_path;
        $return_url = $base_url . 'index.php?dispatch=checkout.thank_you&order_id=' . $order_id;

        error_log('EPAYCO: Redirecting to: ' . $return_url);
        fn_redirect($return_url);
    }
    fn_clear_cart(Tygh::$app['session']['cart']);
    exit;
} else {

    // Initialize Omnipay gateway
    $gateway = Omnipay::create('Epayco');

    // Configure the gateway with configuration values
    $gateway->setUsername('ePayco');
    $gateway->setPkey($processor_data['processor_params']['p_key']);
    $gateway->setPublicKey($processor_data['processor_params']['p_public_key']);
    $gateway->setPrivatekey($processor_data['processor_params']['p_private_key']);
    $gateway->setLang('en');
    $gateway->setTestMode($processor_data['processor_params']['p_test_request'] == 'TRUE' ? true : false);
    $gateway->setCheckoutMode('onpage'); // onpage or redirect

    // Calculate taxes and subtotal
    $p_tax = 0;
    $indice = array_keys($order_info["taxes"]);
    if (!empty($indice) && isset($order_info["taxes"][$indice[0]]["tax_subtotal"]) && $order_info["taxes"][$indice[0]]["tax_subtotal"] != 0) {
        $p_tax = $order_info["taxes"][$indice[0]]["tax_subtotal"];
    }

    $p_amount_base = 0;
    if ($p_tax != 0) {
        $p_amount_base = $order_info['total'] - $p_tax;
    }


    // Build product description
    $i = 0;
    $p_description = "";
    foreach ($order_info['products'] as $k => $v) {
        $i++;
        $p_description .= $v['product'];

        if ($i != count($order_info['products'])) {
            $p_description .= "; ";
        }
    }

    // Response and confirmation URLs
    // Build absolute URLs with protocol, domain and correct path
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];

    // Get base project path (e.g. /Esteban/cscart3/ or just /)
    $script_path = dirname($_SERVER['SCRIPT_NAME']); // Gets the folder where index.php is located
    if ($script_path !== '/' && $script_path !== '\\') {
        $script_path = rtrim($script_path, '/') . '/';
    } else {
        $script_path = '/';
    }

    $base_url = $protocol . $host . $script_path;

    $p_url_response = $base_url . 'index.php?dispatch=payment_notification.response&payment=epayco&order_id=' . $order_id;
    $p_url_confirmation = $base_url . 'index.php?dispatch=payment_notification.confirmation&payment=epayco&order_id=' . $order_id;

    error_log('EPAYCO: Starting payment - Order: ' . $order_id . ', Amount: ' . $order_info['total']);

    /** @var \Tygh\Location\Manager $location_manager */
    $location_manager = Tygh::$app['location'];

    // Build the shopping cart in the format expected by Omnipay
    $cart = array();
    foreach ($order_info['products'] as $product) {
        $cart[] = array(
            'name' => $product['product'],
            'quantity' => $product['amount'],
            'type' => 'product',
            'price' => round($product['price'], 2),
        );
    }

    // Add shipping fee if it exists
    if (isset($order_info['shipping_cost']) && $order_info['shipping_cost'] > 0) {
        $cart[] = array(
            'name' => 'Shipping Fee',
            'quantity' => 1,
            'type' => 'shipping',
            'price' => round($order_info['shipping_cost'], 2),
        );
    }

    // Add discount if it exists
    if (isset($order_info['discount']) && $order_info['discount'] > 0) {
        $cart[] = array(
            'name' => 'Discount',
            'quantity' => 1,
            'type' => 'coupon',
            'price' => round($order_info['discount'], 2),
        );
    }

    // Add tax
    if ($p_tax > 0) {
        $cart[] = array(
            'name' => 'Tax Fee',
            'type' => 'tax',
            'quantity' => 1,
            'price' => round($p_tax, 2),
        );
    }

    // Set the cart in the gateway
    $gateway->setCart($cart);

    // Get billing address and shipping country
    $billAddress = $location_manager->getLocationField($order_info, 'address', '', BILLING_ADDRESS_PREFIX);
    $shipCountry = $location_manager->getLocationField($order_info, 'country', '', SHIPPING_ADDRESS_PREFIX);
    $payerPhone = $location_manager->getLocationField($order_info, 'phone', '', BILLING_ADDRESS_PREFIX);

    // Get full customer name
    $firstName = isset($order_info['b_firstname']) ? $order_info['b_firstname'] : '';
    $lastName = isset($order_info['b_lastname']) ? $order_info['b_lastname'] : '';

    // Get client IP
    $ipClient = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';

    // Make the purchase request
    $response = $gateway->purchase(
        [
            'amount' => $order_info['total'],
            'subTotal' => $p_amount_base,
            'tax' => $p_tax,
            'ico' => 0,
            'currency' => $order_info['secondary_currency'],
            'cancelUrl' => $p_url_response,
            'returnUrl' => $p_url_response,
            'notifyUrl' => $p_url_confirmation,
            'transactionId' => $order_id,
            'description' => $p_description,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $order_info['email'],
            'address' => $billAddress,
            'country' => $shipCountry,
            'ipclient' => $ipClient,
            'hascvv' => true,
            'extras' => [
                'extra1' => $order_id,
                'extra2' => '',
                'extra3' => '',
                'extra4' => '',
            ],
            'extraepayco' =>  "P66"
        ]
    )->send();

    // Process the response
    if ($response->isRedirect()) {
        // Redirect to ePayco form
        error_log('EPAYCO: Enviando a checkout de ePayco');
        echo $response->getRedirectResponse();
        exit;
    } elseif ($response->isSuccessful()) {
        error_log('EPAYCO: Pago exitoso - Reference: ' . $response->getTransactionReference());
        exit;
    } else {
        error_log('EPAYCO: Payment error - ' . $response->getMessage());
        exit;
    }
}
