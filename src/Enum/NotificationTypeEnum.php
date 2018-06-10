<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static NotificationTypeEnum NEW_OWNER()
 * @method static NotificationTypeEnum NEW_SELLER()
 */
class NotificationTypeEnum extends Enum
{
    public const NEW_OWNER = 'new_owner';
    public const NEW_SELLER = 'new_seller';
}