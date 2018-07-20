<?php

namespace App\Swagger\Annotations;

use Swagger\Annotations\Property;
use Swagger\Annotations\Schema;

/**
 * @Annotation
 */
class OfferStatisticSchema extends Schema
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge_recursive([
            'type' => 'object',
            'properties' => [
                new Property(['property' => 'id',     'type' => 'string']),
                new Property(['property' => 'title',  'type' => 'string']),
                new Property(['property' => 'count',  'type' => 'string']),
                new Property(['property' => 'sum',    'type' => 'string']),
                new Property(['property' => 'reason', 'type' => 'string', 'description' => 'Заполняется только для статистики с статусом "rejected"']),
            ]
        ], $properties));
    }
}