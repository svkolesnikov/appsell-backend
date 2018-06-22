<?php

namespace App\DataSource\Dto;

class OfferCompensation
{
    public $type;
    public $description;
    public $currency;
    public $price;
    public $commission;

    public function __construct(array $props)
    {
        $this->type = $props['type'];
        $this->description = $props['description'];
        $this->currency = $props['currency'];
        $this->price = $props['price'];
        $this->commission = $props['commission'];
    }
}