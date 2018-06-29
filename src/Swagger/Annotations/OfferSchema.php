<?php

namespace App\Swagger\Annotations;

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
            'properties' => [
                new Property(['property' => 'title', 'type' => 'string']),
                new Property(['property' => 'description', 'type' => 'string']),
                new Property(['property' => 'type', 'type' => 'string']),
                new Property(['property' => 'active_from', 'type' => 'string']),
                new Property(['property' => 'active_to', 'type' => 'string']),
                new Property(['property' => 'is_active', 'type' => 'boolean']),
            ]
        ], $properties));
    }
}