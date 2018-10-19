<?php

namespace App\Swagger\Annotations;

use Swagger\Annotations\Property;
use Swagger\Annotations\Schema;

/**
 * @Annotation
 */
class PayoutInfoSchema extends Schema
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge_recursive([
            'type' => 'object',
            'properties' => [
                new Property(['property' => 'amount', 'type' => 'integer'])
            ]
        ], $properties));
    }
}