<?php

namespace TestingBundle\Helper;

/**
 * Generates unique test parameters for phpunit test requests
 */
class UniqueParamGenerator
{
    const PATTERN_EMAIL = 'test_%s@gmail.com';

    /**
     * @return string
     */
    public static function generateTestEmail()
    {
        return sprintf(self::PATTERN_EMAIL, uniqid());
    }
}