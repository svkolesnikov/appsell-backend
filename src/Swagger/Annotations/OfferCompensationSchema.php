<?php

namespace App\Swagger\Annotations;

use App\Lib\Enum\CompensationTypeEnum;
use App\Lib\Enum\CurrencyEnum;
use Swagger\Annotations\Property;
use Swagger\Annotations\Schema;

/**
 * @Annotation
 */
class OfferCompensationSchema extends Schema
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge_recursive([
            'type' => 'object',
            'required' => ['type', 'currency', 'price', 'description'],
            'properties' => [
                new Property(['property' => 'type', 'type' => 'string', 'enum' => array_values(CompensationTypeEnum::toArray())]),
                new Property(['property' => 'description', 'type' => 'string']),
                new Property(['property' => 'currency', 'type' => 'string', 'enum' => array_values(CurrencyEnum::toArray())]),
                new Property(['property' => 'price', 'type' => 'number'])
            ]
        ], $properties));
    }
}