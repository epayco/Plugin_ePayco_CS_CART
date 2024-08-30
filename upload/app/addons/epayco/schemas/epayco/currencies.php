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

defined('BOOTSTRAP') or die('Access denied');

$schema = array(
    'COP' => array(
        'name' => 'currency_code_cod',
        'code' => 'COD',
        'id' => 999,
        'supports' => array('standard', 'checkout'),
        'active' => true
    ),
    'USD' => array(
        'name' => 'currency_code_usd',
        'code' => 'USD',
        'supports' => array('standard', 'checkout'),
        'id' => 840,
        'active' => true
    )
);

return $schema;
