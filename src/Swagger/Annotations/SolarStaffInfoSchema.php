<?php

namespace App\Swagger\Annotations;

use Swagger\Annotations\Property;
use Swagger\Annotations\Schema;

/**
 * @Annotation
 */
class SolarStaffInfoSchema extends Schema
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge_recursive([
            'type' => 'object',
            'properties' => [
                new Property(['property' => 'oferta_url', 'type' => 'string']),
                new Property(['property' => 'login_url', 'type' => 'string']),
            ]
        ], $properties));
    }
}