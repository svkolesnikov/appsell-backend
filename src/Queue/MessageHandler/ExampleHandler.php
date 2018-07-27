<?php

namespace App\Queue\MessageHandler;

use Interop\Queue\PsrProcessor;

class ExampleHandler implements HandlerInterface
{
    public function handle(array $message): string
    {
        var_dump('example!!!', $message);
        return PsrProcessor::ACK;
    }
}