<?php

namespace App\Exception;

use Throwable;

class AppException extends \Exception
{
    public function __construct(string $message = '', Throwable $previous = null, int $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }
}