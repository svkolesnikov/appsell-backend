<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static OfferLinkTypeEnum GOOGLE_PLAY()
 * @method static OfferLinkTypeEnum WEB()
 * @method static OfferLinkTypeEnum APP_STORE()
 */
final class OfferLinkTypeEnum extends Enum
{
    public const GOOGLE_PLAY = 'google_play';
    public const APP_STORE   = 'app_store';
    public const WEB         = 'web';
}