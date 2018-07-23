<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static OfferTypeEnum APP()
 * @method static OfferTypeEnum SERVICE()
 */
final class OfferTypeEnum extends Enum
{
    use TitlesAwareTrait;

    public const APP = 'app';

    public const SERVICE = 'service';

    protected static $titles = [
        self::APP       => 'Приложение',
        self::SERVICE   => 'Услуга'
    ];
}