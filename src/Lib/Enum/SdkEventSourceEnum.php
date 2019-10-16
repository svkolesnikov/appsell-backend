<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static SdkEventSourceEnum APP()
 * @method static SdkEventSourceEnum TUNE()
 * @method static SdkEventSourceEnum APPSFLYER()
 * @method static SdkEventSourceEnum CSV()
 */
final class SdkEventSourceEnum extends Enum
{
    public const APP = 'app';
    public const TUNE = 'tune';
    public const APPSFLYER = 'appsflyer';
    public const CSV = 'csv';
}