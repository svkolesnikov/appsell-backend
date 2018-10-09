<?php

namespace App\Swagger\Annotations;

use Swagger\Annotations\Property;
use Swagger\Annotations\Schema;

/**
 * @Annotation
 */
class UserSchema extends Schema
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge_recursive([
            'type' => 'object',
            'properties' => [
                new Property(['property' => 'email', 'type' => 'string']),
                new Property(['property' => 'group', 'type' => 'string']),
                new Property(['property' => 'phone', 'type' => 'string']),
                new Property(['property' => 'lastname', 'type' => 'string']),
                new Property(['property' => 'firstname', 'type' => 'string']),
                new Property(['property' => 'company_id', 'type' => 'string']),
                new Property(['property' => 'company_title', 'type' => 'string']),
                new Property(['property' => 'is_solar_staff_connected', 'type' => 'boolean'])
            ]
        ], $properties));
    }
}