<?php

namespace App\Exception\Admin;

class UserNotFoundException extends AdminException
{
    public function __construct(string $message = 'Пользователь не найден', $code = 404)
    {
        parent::__construct($message, $code);
    }
}