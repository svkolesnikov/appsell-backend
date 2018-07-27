<?php

namespace App\Queue\MessageHandler;

use Interop\Queue\PsrProcessor;

class MainHandler implements HandlerInterface
{
    public function handle(array $message): string
    {
        var_dump('main!!!', $message);
        return PsrProcessor::ACK;
    }
}