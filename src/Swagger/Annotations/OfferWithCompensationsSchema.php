<?php

namespace App\Swagger\Annotations;

use Swagger\Annotations\Property;
use Swagger\Annotations\Schema;

/**
 * @Annotation
 */
class OfferWithCompensationsSchema extends Schema
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge_recursive([
            'type' => 'object',
            'properties' => [
                new Property(['property' => 'id', 'type' => 'string']),
                new Property(['property' => 'title', 'type' => 'string']),
                new Property(['property' => 'description', 'type' => 'string']),
                new Property(['property' => 'type', 'type' => 'string']),
                new Property(['property' => 'compensations', 'type' => 'array', 'items' => new Schema([
                    'properties' => [
                        new Property(['property' => 'type', 'type' => 'string']),
                        new Property(['property' => 'description', 'type' => 'string']),
                        new Property(['property' => 'currency', 'type' => 'string']),
                        new Property(['property' => 'price', 'type' => 'number'])
                    ]
                ])]),
                new Property(['property' => 'links', 'type' => 'array', 'items' => new Schema([
                    'properties' => [
                        new Property(['property' => 'type', 'type' => 'string'])
                    ]
                ])]),
            ]
        ], $properties));
    }
}