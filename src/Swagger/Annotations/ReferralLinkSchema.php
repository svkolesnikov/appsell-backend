<?php

namespace App\Swagger\Annotations;

use Swagger\Annotations\Property;
use Swagger\Annotations\Schema;

/**
 * @Annotation
 */
class ReferralLinkSchema extends Schema
{
    public function __construct(array $properties)
    {
        parent::__construct(array_merge_recursive([
            'type' => 'object',
            'properties' => [
                new Property(['property' => 'url', 'type' => 'string']),
                new Property(['property' => 'qrcode', 'type' => 'string', 'description' => 'URL для перехода, закодированный в QR код']),
            ]
        ], $properties));
    }
}