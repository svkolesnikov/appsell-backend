<?php

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static OfferLinkTypeEnum GOOGLE()
 * @method static OfferLinkTypeEnum APPLE()
 */
final class OfferLinkTypeEnum extends Enum
{
    public const GOOGLE_PLAY = 'google_play';
    public const APP_STORE   = 'app_store';
    public const WEB         = 'web';
}