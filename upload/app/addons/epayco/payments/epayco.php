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

// Cargar Omnipay desde el addon epayco
$omnipay_path = dirname(__FILE__) . '/../lib/src/';
require_once $omnipay_path . 'vendor/autoload.php';

use Omnipay\Omnipay;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

error_log('EPAYCO: ===== INICIO EPAYCO.PHP =====');
error_log('EPAYCO PAYMENT_NOTIFICATION definido: ' . (defined('PAYMENT_NOTIFICATION') ? 'SI' : 'NO'));
error_log('EPAYCO $_REQUEST: ' . print_r($_REQUEST, true));
error_log('EPAYCO $_GET: ' . print_r($_GET, true));
error_log('EPAYCO $_POST: ' . print_r($_POST, true));

// Detectar si es una notificación de pago por dispatch o por PAYMENT_NOTIFICATION
$is_payment_notification = defined('PAYMENT_NOTIFICATION') || 
                           (isset($_REQUEST['dispatch']) && strpos($_REQUEST['dispatch'], 'payment_notification') === 0) ||
                           (!empty($_POST) && !empty($_REQUEST['order_id']));

error_log('EPAYCO is_payment_notification: ' . ($is_payment_notification ? 'SI' : 'NO'));

if ($is_payment_notification) {

    error_log('EPAYCO: ===== INICIANDO PAYMENT_NOTIFICATION =====');
    error_log('EPAYCO REQUEST: ' . print_r($_REQUEST, true));
    error_log('EPAYCO GET: ' . print_r($_GET, true));
    error_log('EPAYCO POST: ' . print_r($_POST, true));

    // states success
    $statusSuccess = array(1, 3);

    // Get the processor data
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $_REQUEST['order_id']);
    $processor_data = fn_get_payment_method_data($payment_id);
    $order_info = fn_get_order_info($_REQUEST['order_id']);

    error_log('EPAYCO: Payment ID: ' . $payment_id);
    error_log('EPAYCO: Order Info: ' . print_r($order_info, true));

    $pp_response = array();
    $pp_response['order_status'] = (in_array($_REQUEST['x_cod_response'], $statusSuccess)) ? 'P' : 'F';
    $pp_response['reason_text'] = $_REQUEST['x_response_reason_text'];
    $pp_response['transaction_id'] = $_REQUEST['x_transaction_id'];
    $confirmation = false;
    $ref_payco = isset($_GET['ref_payco']) ? $_GET['ref_payco'] : '';
    
    error_log('EPAYCO: ref_payco = ' . $ref_payco);
    
    if(empty($ref_payco)){
        // Primera llamada - confirmar con los datos POST
        error_log('EPAYCO: Primera llamada - usando datos POST');
        $validationData = $_REQUEST;
        $confirmation = true;
    }else{
        // Redirección de ePayco - validar contra el servidor de ePayco
        error_log('EPAYCO: Redirección detectada - validando con servidor remoto');
        $url = 'https://secure.epayco.io/validation/v1/reference/'.$ref_payco;
        error_log('EPAYCO: Llamando URL: ' . $url);
        
        $responseData = @file_get_contents($url);
        error_log('EPAYCO: Respuesta remota: ' . $responseData);
        
        if($responseData){
            $jsonData = @json_decode($responseData, true);
            error_log('EPAYCO: JSON decodificado: ' . print_r($jsonData, true));
            $validationData = isset($jsonData['data']) ? $jsonData['data'] : array();
        }else{
            // Si no puede validar con el servidor, usar los datos POST si existen
            error_log('EPAYCO: No hay respuesta remota, usando datos POST');
            $validationData = !empty($_REQUEST) ? $_REQUEST : array();
        }
    }
    
    error_log('EPAYCO: Validation Data: ' . print_r($validationData, true));

    if(!empty($validationData)){
        error_log('EPAYCO: validationData no está vacío');
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
        
        error_log('EPAYCO: Datos extraídos - x_signature: '.$x_signature);
        error_log('EPAYCO: x_cod_transaction_state: '.$x_cod_transaction_state);
        error_log('EPAYCO: x_ref_payco: '.$x_ref_payco);
        error_log('EPAYCO: x_amount: '.$x_amount);
        error_log('EPAYCO: x_id_invoice/order_id_: '.$order_id_);
    }else{
        error_log('EPAYCO: validationData está vacío');
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
    
    error_log('EPAYCO: Procesando order_id: ' . $order_id);
    error_log('EPAYCO: Verificando: !empty($order_id) = ' . (!empty($order_id) ? 'true' : 'false'));
    error_log('EPAYCO: Verificando: !empty($validationData) = ' . (!empty($validationData) ? 'true' : 'false'));

    if(!empty($order_id) && !empty($validationData)){
        error_log('EPAYCO: ===== VALIDANDO PAGO =====');
        $signature = hash('sha256',
            trim($processor_data['processor_params']['p_cust_id_cliente']).'^'
            .trim($processor_data['processor_params']['p_key']).'^'
            .$x_ref_payco.'^'
            .$x_transaction_id.'^'
            .$x_amount.'^'
            .$x_currency_code
        );
        error_log('EPAYCO: Firma calculada: ' . $signature);
        error_log('EPAYCO: Firma recibida: ' . $x_signature);
        
        $isTestTransaction = $x_test_request == 'TRUE' ? "yes" : "no";
        $isTestMode = $isTestTransaction == "yes" ? "true" : "false";
        $isTestPluginMode = $processor_data['processor_params']['p_test_request']  == 'TRUE' ? "yes" : "no";

        error_log('EPAYCO: Monto orden: ' . $order_info['total']);
        error_log('EPAYCO: Monto recibido: ' . $x_amount);

        if(floatval($order_info['total']) == floatval($x_amount)){
            error_log('EPAYCO: Montos coinciden');
            if("yes" == $isTestPluginMode){
                error_log('EPAYCO: Modo TEST - Validación = true');
                $validation = true;
            }
            if("no" == $isTestPluginMode ){
                error_log('EPAYCO: Modo PRODUCCIÓN - Validando aprobación');
                if($x_approval_code != "000000" && $x_cod_transaction_state == 1){
                    error_log('EPAYCO: Validación = true (aprobado)');
                    $validation = true;
                }else{
                    if($x_cod_transaction_state != 1){
                        error_log('EPAYCO: Validación = true (estado diferente de 1)');
                        $validation = true;
                    }else{
                        error_log('EPAYCO: Validación = false (código 000000 y estado 1)');
                        $validation = false;
                    }
                }
            }
        }else{
            error_log('EPAYCO: Montos NO coinciden - Validación = false');
            $validation = false;
        }

        error_log('EPAYCO: Validación final: ' . ($validation ? 'true' : 'false'));
        error_log('EPAYCO: Comparando firmas: ' . ($signature == $x_signature ? 'coinciden' : 'NO coinciden'));
        error_log('EPAYCO: Firma calculada: ' . $signature);
        error_log('EPAYCO: Firma recibida: ' . $x_signature);

        // En modo TEST, permite validación sin firma si x_test_request es TRUE
        $allow_without_signature = ($processor_data['processor_params']['p_test_request'] == 'TRUE' && $x_test_request == 'TRUE');
        
        if(($signature == $x_signature || $allow_without_signature) && $validation){
            error_log('EPAYCO: ===== PAGO VALIDADO EXITOSAMENTE =====');
            switch ($x_cod_transaction_state) {
                case 1: {
                    error_log('EPAYCO: Estado 1 - Approved (C)');
                    $pp_response['order_status'] = 'C';
                } break;
                case 2: {
                    error_log('EPAYCO: Estado 2 - Rejected (D)');
                    $pp_response['order_status'] = 'D';
                } break;
                case 3: {
                    error_log('EPAYCO: Estado 3 - Bank Processing (Y)');
                    $pp_response['order_status'] = 'Y';
                } break;
                case 4: {
                    error_log('EPAYCO: Estado 4 - Pending (I)');
                    $pp_response['order_status'] = 'I';
                } break;
                case 6: {
                    error_log('EPAYCO: Estado 6 - Refunded (I)');
                    $pp_response['order_status'] = 'I';
                } break;
                case 10:{
                    error_log('EPAYCO: Estado 10 - Abandoned (I)');
                    $pp_response['order_status'] = 'I';
                } break;
                case 11:{
                    error_log('EPAYCO: Estado 11 - Expired (I)');
                    $pp_response['order_status'] = 'I';
                } break;
                default: {
                    error_log('EPAYCO: Estado desconocido (O)');
                    $pp_response['order_status'] = 'O';
                } break;
            }

            $pp_response['reason_text'] = $validationData['x_response_reason_text'] ?? 'Payment processed';
            $pp_response['transaction_id'] = $validationData['x_transaction_id'] ?? '';
            error_log('EPAYCO: Actualizando estado de orden: ' . $pp_response['order_status']);
            if (fn_check_payment_script('epayco.php', $order_id)) {
                fn_update_order_payment_info($order_id, $pp_response);
                fn_change_order_status($order_id, $pp_response['order_status'], '', false);
            }
        }else{
            error_log('EPAYCO: ===== VALIDACIÓN FALLIDA =====');
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = __('text_transaction_declined');
            if (fn_check_payment_script('epayco.php', $order_id)) {
                fn_update_order_payment_info($order_id, $pp_response);
                fn_change_order_status($order_id, $pp_response['order_status'], '', false);
            }
        }

    }else{
        error_log('EPAYCO: ===== ERROR: order_id o validationData vacíos =====');
        $pp_response['order_status'] = 'F';
        $pp_response['reason_text'] = __('text_transaction_declined');
        if (fn_check_payment_script('epayco.php', $order_id)) {
            fn_update_order_payment_info($order_id, $pp_response);
            fn_change_order_status($order_id, $pp_response['order_status'], '', false);
        }
    }
    fn_finish_payment($order_id, $pp_response);
    
    error_log('EPAYCO: Confirmación = ' . ($confirmation ? 'true' : 'false'));
    error_log('EPAYCO: Order Status Final = ' . $pp_response['order_status']);
    
    // Siempre ejecutar las rutinas de colocación de orden
    fn_order_placement_routines('route', $order_id);
    
    // Si fue una confirmación por webhook, mostrar el código
    if($confirmation){
        error_log('EPAYCO: Enviando respuesta de webhook');
        echo "code response: ".$x_cod_transaction_state;
    }else{
        // Redirigir a la página de agradecimiento de CS-Cart
        $return_url = fn_url('checkout.thank_you', 'C', 'relative', array('order_id' => $order_id));
        error_log('EPAYCO: Redirigiendo a: ' . $return_url);
        fn_redirect($return_url);
    }
    fn_clear_cart(Tygh::$app['session']['cart']);
    exit;
} else {

    // DEBUG: Verificar que llegamos aquí
    error_log('EPAYCO DEBUG: Iniciando proceso de pago para order_id: ' . $order_id);

    // Inicializar el gateway de Omnipay
    $gateway = Omnipay::create('Epayco');
    
    // Configurar el gateway con valores de la configuración
    $gateway->setUsername('ePayco');
    $gateway->setPkey($processor_data['processor_params']['p_key']);
    $gateway->setPrivatekey($processor_data['processor_params']['p_privatekey']);
    $gateway->setPublicKey($processor_data['processor_params']['p_publickey']);
    $gateway->setLang('en');
    $gateway->setTestMode($processor_data['processor_params']['p_test_request'] == 'TRUE' ? true : false);
    $gateway->setCheckoutMode('onpage'); // onpage or redirect

    // Calcular impuestos y subtotal
    $p_tax = 0;
    $indice = array_keys($order_info["taxes"]);
    if (!empty($indice) && isset($order_info["taxes"][$indice[0]]["tax_subtotal"]) && $order_info["taxes"][$indice[0]]["tax_subtotal"] != 0) {
        $p_tax = $order_info["taxes"][$indice[0]]["tax_subtotal"];
    }

    $p_amount_base = 0;
    if ($p_tax != 0) {
        $p_amount_base = $order_info['total'] - $p_tax;
    }


    // Construir la descripción de los productos
    $i = 0;
    $p_description = "";
    foreach ($order_info['products'] as $k => $v) {
        $i++;
        $p_description .= $v['product'];

        if ($i != count($order_info['products'])) {
            $p_description .= "; ";
        }
    }

    // URLs de respuesta y confirmación
    $p_url_response = fn_url('payment_notification.response', 'C', 'full', array('payment' => 'epayco', 'order_id' => $order_id));
    $p_url_confirmation = fn_url('payment_notification.confirmation', 'C', 'full', array('payment' => 'epayco', 'order_id' => $order_id));
    
    error_log('EPAYCO: URL Response: ' . $p_url_response);
    error_log('EPAYCO: URL Confirmation: ' . $p_url_confirmation);

    /** @var \Tygh\Location\Manager $location_manager */
    $location_manager = Tygh::$app['location'];

    // Construir el carrito de compras en el formato esperado por Omnipay
    $cart = array();
    foreach ($order_info['products'] as $product) {
        $cart[] = array(
            'name' => $product['product'],
            'quantity' => $product['amount'],
            'type' => 'product',
            'price' => round($product['price'], 2),
        );
    }
    
    // Agregar shipping fee si existe
    if (isset($order_info['shipping_cost']) && $order_info['shipping_cost'] > 0) {
        $cart[] = array(
            'name' => 'Shipping Fee',
            'quantity' => 1,
            'type' => 'shipping',
            'price' => round($order_info['shipping_cost'], 2),
        );
    }
    
    // Agregar discount si existe
    if (isset($order_info['discount']) && $order_info['discount'] > 0) {
        $cart[] = array(
            'name' => 'Discount',
            'quantity' => 1,
            'type' => 'coupon',
            'price' => round($order_info['discount'], 2),
        );
    }
    
    // Agregar tax
    if ($p_tax > 0) {
        $cart[] = array(
            'name' => 'Tax Fee',
            'type' => 'tax',
            'quantity' => 1,
            'price' => round($p_tax, 2),
        );
    }
    
    // Configurar el carrito en el gateway
    $gateway->setCart($cart);
    
    // Obtener dirección de facturación y país de envío
    $billAddress = $location_manager->getLocationField($order_info, 'address', '', BILLING_ADDRESS_PREFIX);
    $shipCountry = $location_manager->getLocationField($order_info, 'country', '', SHIPPING_ADDRESS_PREFIX);
    $payerPhone = $location_manager->getLocationField($order_info, 'phone', '', BILLING_ADDRESS_PREFIX);
    
    // Obtener nombre completo del cliente
    $firstName = isset($order_info['b_firstname']) ? $order_info['b_firstname'] : '';
    $lastName = isset($order_info['b_lastname']) ? $order_info['b_lastname'] : '';
    
    // Obtener IP del cliente
    $ipClient = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    
    // Realizar la petición de compra
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

    // Procesar la respuesta
    if ($response->isRedirect()) {
        // DEBUG: Verificar redirección
        error_log('EPAYCO DEBUG: Redirigiendo a checkout de ePayco');
        
        // Obtener la respuesta del formulario de redirección
        echo $response->getRedirectResponse();
        exit;
    } elseif ($response->isSuccessful()) {
        // El pago fue exitoso
        echo 'Payment successful! Transaction reference: ' . $response->getTransactionReference();
        exit;
    } else {
        // El pago falló
        echo $response->getMessage();
        exit;
    }
}