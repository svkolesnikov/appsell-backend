<?php

namespace App\Queue\MessageHandler;

interface HandlerInterface
{
    public function handle(array $message): string;
}