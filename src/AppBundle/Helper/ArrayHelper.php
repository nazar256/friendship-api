<?php

/**
 * Class container
 * PHP version 5.6
 * @category Class
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     localhost
 */

namespace AppBundle\Helper;

/**
 * Contains functions for array processing
 * @category Helper
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     localhost
 */
class ArrayHelper
{
    /**
     * The same result as array_unique(array_merge($set1, $set2)) but maybe faster
     * @param array $set1 first set
     * @param array $set2 another set
     * @return array
     */
    public static function sumSets(array $set1, array $set2)
    {
        return array_keys(array_flip($set1) + array_flip($set2));
    }
}