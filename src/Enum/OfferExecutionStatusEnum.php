<?php

namespace App\Enum;
use MyCLabs\Enum\Enum;

/**
 * @method static OfferExecutionStatusEnum PROCESSING()
 * @method static OfferExecutionStatusEnum COMPLETE()
 * @method static OfferExecutionStatusEnum REJECTED()
 */
class OfferExecutionStatusEnum extends Enum
{
    public const PROCESSING = 'processing';
    public const COMPLETE   = 'complete';
    public const REJECTED   = 'rejected';
}