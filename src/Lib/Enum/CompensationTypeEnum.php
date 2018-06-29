<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static CompensationTypeEnum BASE()
 * @method static CompensationTypeEnum ADDITIONAL()
 */
final class CompensationTypeEnum extends Enum
{
    use TitlesAwareTrait;

    public const BASE = 'base';
    public const ADDITIONAL = 'additional';

    protected static $titles = [
        self::BASE => 'Базовая',
        self::ADDITIONAL => 'Дополнительная'
    ];
}