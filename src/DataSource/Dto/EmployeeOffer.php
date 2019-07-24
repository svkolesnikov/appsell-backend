<?php

namespace App\DataSource\Dto;

use App\Lib\Enum\CompensationTypeEnum;

class EmployeeOffer
{
    public $id;
    public $title;
    public $description;
    public $type;
    public $image;

    /** @var OfferCompensation[] */
    public $compensations = [];

    /** @var OfferLink[] */
    public $links = [];

    public function __construct(array $props)
    {
        $this->id           = $props['id'];
        $this->title        = $props['title'];
        $this->description  = $props['description'];
        $this->type         = $props['type'];
        $this->image        = $props['image'];

        $this->links = array_map(function (array $comp) {
            return new OfferLink($comp);
        }, $props['links']);

        if (\count($props['compensations']) > 0) {
            $resultCompensation = [
                'type'        => $props['compensations'][0]['type'],
                'description' => $props['compensations'][0]['description'],
                'currency'    => $props['compensations'][0]['currency'],
                'price'       => 0,
            ];

            foreach ($props['compensations'] as $c) {
                if ($c['type'] === CompensationTypeEnum::BASE) {
                    $resultCompensation['type']         = $c['type'];
                    $resultCompensation['description']  = $c['description'];
                    $resultCompensation['currency']     = $c['currency'];
                }

                $resultCompensation['price'] += $c['price'];
            }

            $this->compensations = [new OfferCompensation($resultCompensation)];
        }
    }
}