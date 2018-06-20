<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static UserGroupEnum OWNER()
 * @method static UserGroupEnum SELLER()
 * @method static UserGroupEnum EMPLOYEE()
 */
final class UserGroupEnum extends Enum
{
    public const OWNER    = 'owner';
    public const SELLER   = 'seller';
    public const EMPLOYEE = 'employee';
}