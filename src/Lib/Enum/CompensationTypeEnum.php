<?php

namespace App\Lib\Enum;

/**
 * @method static CompensationTypeEnum BASE()
 * @method static CompensationTypeEnum ADDITIONAL()
 */
final class CompensationTypeEnum extends BaseEnum
{
    public const BASE = 'base';
    public const ADDITIONAL = 'additional';

    protected static $titles = [
        self::BASE => 'Базовая',
        self::ADDITIONAL => 'Дополнительная'
    ];
}