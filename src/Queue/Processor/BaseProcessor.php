<?php

namespace App\Queue\Processor;

use Enqueue\Consumption\QueueSubscriberInterface;
use Interop\Queue\PsrProcessor;

abstract class BaseProcessor implements PsrProcessor, QueueSubscriberInterface
{
    abstract public static function getQueue(): string;

    public static function getSubscribedQueues(): array
    {
        return [self::getQueue()];
    }
}