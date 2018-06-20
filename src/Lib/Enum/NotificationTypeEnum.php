<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static NotificationTypeEnum NEW_OWNER()
 * @method static NotificationTypeEnum NEW_SELLER()
 * @method static NotificationTypeEnum NEW_EMPLOYEE()
 * @method static NotificationTypeEnum CONFIRM_EMAIL()
 */
final class NotificationTypeEnum extends Enum
{
    public const NEW_OWNER = 'new_owner';
    public const NEW_SELLER = 'new_seller';
    public const NEW_EMPLOYEE = 'new_employee';
    public const CONFIRM_EMAIL = 'confirm_email';
}