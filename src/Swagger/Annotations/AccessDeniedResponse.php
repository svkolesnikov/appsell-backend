<?php

namespace App\Swagger\Annotations;

/**
 * @Annotation
 */
class AccessDeniedResponse extends ErrorResponse
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge([
            'response'    => 403,
            'description' => 'Access denied'
        ], $properties));
    }
}