<?php

namespace App\DataSource\Dto;

class OfferLink
{
    public $type;

    public function __construct(array $props)
    {
        $this->type = $props['type'];
    }
}