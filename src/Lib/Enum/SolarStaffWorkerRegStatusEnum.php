<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static self SUCCESS()
 * @method static self NULL()
 */
final class SolarStaffWorkerRegStatusEnum extends Enum
{
    public const SUCCESS = 'reg_success';
    public const NULL = 'reg_null';
}