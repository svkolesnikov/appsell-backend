<?php

namespace App\Swagger\Annotations;

/**
 * @Annotation
 */
class NotFoundResponse extends ErrorResponse
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge([
            'response'    => 404,
            'description' => 'Not found'
        ], $properties));
    }
}