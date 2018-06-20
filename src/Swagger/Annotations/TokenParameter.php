<?php

namespace App\Swagger\Annotations;

use Swagger\Annotations\Parameter;

/**
 * @Annotation
 */
class TokenParameter extends Parameter
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge([
            'name' => 'Authorization',
            'description' => 'Токен доступа. Пример: "Authorization: Bearer ..."',
            'type' => 'string',
            'required' => true,
            'in' => 'header'
        ], $properties));
    }
}