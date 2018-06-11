<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static OfferTypeEnum APP()
 * @method static OfferTypeEnum SERVICE()
 */
final class OfferTypeEnum extends Enum
{
    public const APP = 'app';
    public const SERVICE = 'service';
}