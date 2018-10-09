<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static ActionLogItemTypeEnum SDK_EVENT()
 * @method static ActionLogItemTypeEnum SOLAR_STAFF_REGISTRATION()
 */
final class ActionLogItemTypeEnum extends Enum
{
    public const SDK_EVENT = 'sdk_event';
    public const SOLAR_STAFF_REGISTRATION = 'solar_staff_registration';
}