<?php

namespace App\Swagger\Annotations;

use Swagger\Annotations\Items;
use Swagger\Annotations\Property;
use Swagger\Annotations\Response;
use Swagger\Annotations\Schema;

abstract class ErrorResponse extends Response
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge([

            'response'      => 500,
            'description'   => 'Internal Server Error',

            'schema'        => new Schema([
                'type'          => 'object',
                'properties'    => [
                    new Property(['property' => 'message', 'type' => 'string']),
                    new Property(['property' => 'details', 'type' => 'array', 'items' => new Items([
                        'properties' => [
                            new Property(['property' => 'key', 'type' => 'string'])
                        ]
                    ])])
                ]
            ])

        ], $properties));
    }
}