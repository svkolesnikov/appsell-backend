<?php

namespace App\Queue\Processor;

use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;

class ExampleProcessor extends BaseProcessor
{
    public static function getQueue(): string
    {
        return 'example';
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        var_dump(json_decode($message->getBody()));
        return self::ACK;
    }
}