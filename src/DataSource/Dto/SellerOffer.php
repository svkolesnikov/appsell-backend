<?php

namespace App\DataSource\Dto;

class SellerOffer
{
    public $id;
    public $title;
    public $description;
    public $type;
    public $commission;

    /** @var OfferCompensation[] */
    public $compensations;

    public function __construct(array $props)
    {
        $this->id = $props['id'];
        $this->title = $props['title'];
        $this->description = $props['description'];
        $this->type = $props['type'];
        $this->commission = $props['commission'];

        $this->compensations = array_map(function (array $comp) {
            return new OfferCompensation($comp);
        }, $props['compensations']);
    }
}