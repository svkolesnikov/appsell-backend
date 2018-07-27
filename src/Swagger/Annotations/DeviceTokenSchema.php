<?php

namespace App\Swagger\Annotations;

use App\Lib\Enum\DeviceTokenTypeEnum;
use Swagger\Annotations\Property;
use Swagger\Annotations\Schema;

/**
 * @Annotation
 */
class DeviceTokenSchema extends Schema
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge_recursive([
            'type' => 'object',
            'required' => ['type', 'token'],
            'properties' => [
                new Property(['property' => 'token', 'type' => 'string']),
                new Property(['property' => 'type', 'type' => 'string', 'enum' => array_values(DeviceTokenTypeEnum::toArray())]),
            ]
        ], $properties));
    }
}