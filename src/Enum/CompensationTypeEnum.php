<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static CompensationTypeEnum BASE()
 * @method static CompensationTypeEnum ADDITIONAL()
 */
final class CompensationTypeEnum extends Enum
{
    public const BASE = 'base';
    public const ADDITIONAL = 'additional';
}