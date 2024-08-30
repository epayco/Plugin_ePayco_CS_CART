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

namespace Tygh\Enum\Addons\Epayco;

use ReflectionClass;

class Processors
{
    const STANDARD = 'epayco.php';

    public static function getAll()
    {
        $reflector = new ReflectionClass(__CLASS__);

        return $reflector->getConstants();
    }

    public static function getAllWithTypes()
    {
        return array(
            self::STANDARD => ProcessorTypes::STANDARD,
        );
    }
}
