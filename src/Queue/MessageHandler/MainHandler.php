<?php

namespace App\Queue\MessageHandler;

use Interop\Queue\Processor;

class MainHandler implements HandlerInterface
{
    public function handle(array $message): string
    {
        var_dump('main!!!', $message);
        return Processor::ACK;
    }
}