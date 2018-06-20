<?php

namespace App\Swagger\Annotations;

/**
 * @Annotation
 */
class BadRequestResponse extends ErrorResponse
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge([
            'response'    => 400,
            'description' => ''
        ], $properties));
    }
}