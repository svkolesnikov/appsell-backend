<?php

namespace App\Exception\Admin;

use App\Exception\AppException;

class AdminException extends AppException
{
    public function __construct(string $message, $code = 0)
    {
        parent::__construct($message, $code);
    }
}