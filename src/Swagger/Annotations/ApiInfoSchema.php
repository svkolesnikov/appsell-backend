<?php

namespace App\Swagger\Annotations;

use Swagger\Annotations\Property;
use Swagger\Annotations\Schema;

/**
 * @Annotation
 */
class ApiInfoSchema extends Schema
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge_recursive([
            'type' => 'object',
            'properties' => [
                new Property(['property' => 'end_user_agreement', 'type' => 'string']),
                new Property(['property' => 'privacy_policy', 'type' => 'string']),
                new Property(['property' => 'terms_of_use', 'type' => 'string'])
            ]
        ], $properties));
    }
}