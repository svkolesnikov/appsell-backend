<?php

namespace App\Swagger\Annotations;

/**
 * @Annotation
 */
class UnauthorizedResponse extends ErrorResponse
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge([
            'response' => 401,
            'description' => 'Authentication required'
        ], $properties));
    }
}