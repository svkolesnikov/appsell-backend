<?php

namespace App\Exception\Admin;

class OfferNotFoundException extends AdminException
{
    public function __construct(string $message = 'Оффер не найден', $code = 404)
    {
        parent::__construct($message, $code);
    }
}