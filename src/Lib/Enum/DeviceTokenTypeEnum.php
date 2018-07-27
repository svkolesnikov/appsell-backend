<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static DeviceTokenTypeEnum IOS()
 * @method static DeviceTokenTypeEnum ANDROID()
 */
final class DeviceTokenTypeEnum extends Enum
{
    public const IOS = 'ios';
    public const ANDROID = 'android';
}