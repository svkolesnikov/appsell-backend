<?php

namespace App\Swagger\Annotations;

use App\Lib\Enum\OfferTypeEnum;
use Swagger\Annotations\Property;
use Swagger\Annotations\Schema;

/**
 * @Annotation
 */
class OfferSchema extends Schema
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge_recursive([
            'type' => 'object',
            'required' => ['title', 'type'],
            'properties' => [
                new Property(['property' => 'title', 'type' => 'string']),
                new Property(['property' => 'description', 'type' => 'string']),
                new Property(['property' => 'type', 'type' => 'string', 'enum' => array_values(OfferTypeEnum::toArray())]),
            ]
        ], $properties));
    }
}