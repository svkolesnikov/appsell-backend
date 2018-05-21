<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static StoreEnum GOOGLE()
 * @method static StoreEnum APPLE()
 */
final class StoreEnum extends Enum
{
    public const GOOGLE = 'google';
    public const APPLE  = 'apple';
}