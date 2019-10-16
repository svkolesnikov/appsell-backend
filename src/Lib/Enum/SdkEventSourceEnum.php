<?php

namespace App\Lib\Enum;

use MyCLabs\Enum\Enum;

/**
 * @method static static APP()
 * @method static static TUNE()
 * @method static static APPSFLYER()
 * @method static static CSV()
 */
final class SdkEventSourceEnum extends Enum
{
    public const APP = 'app';
    public const TUNE = 'tune';
    public const APPSFLYER = 'appsflyer';
    public const CSV = 'csv';
}