<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static static APPSFLYER_EVENT()
 * @method static static SDK_EVENT()
 * @method static static SOLAR_STAFF_REGISTRATION()
 * @method static static SOLAR_STAFF_PAYOUT()
 */
final class ActionLogItemTypeEnum extends Enum
{
    public const APPSFLYER_EVENT = 'appsflyer_event';
    public const SDK_EVENT = 'sdk_event';
    public const SOLAR_STAFF_REGISTRATION = 'solar_staff_registration';
    public const SOLAR_STAFF_PAYOUT = 'solar_staff_payout';
}