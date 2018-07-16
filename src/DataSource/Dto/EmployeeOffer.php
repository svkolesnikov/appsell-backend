<?php

namespace App\DataSource\Dto;

class EmployeeOffer
{
    public $id;
    public $title;
    public $description;
    public $type;

    /** @var OfferCompensation[] */
    public $compensations;

    /** @var OfferLink[] */
    public $links;

    public function __construct(array $props)
    {
        $this->id = $props['id'];
        $this->title = $props['title'];
        $this->description = $props['description'];
        $this->type = $props['type'];

        $this->compensations = array_map(function (array $comp) {
            return new OfferCompensation($comp);
        }, $props['compensations']);

        $this->links = array_map(function (array $comp) {
            return new OfferLink($comp);
        }, $props['links']);
    }
}