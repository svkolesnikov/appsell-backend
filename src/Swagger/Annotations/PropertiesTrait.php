<?php

namespace App\Swagger\Annotations;

use Swagger\Annotations\Items;
use Swagger\Annotations\Property;

trait PropertiesTrait
{
    /**
     * @return array|Property[]
     */
    public function getCurrentUserProperties(): array
    {
        return [
            new Property(['property' => '_id', 'type' => 'string']),
            new Property(['property' => 'username', 'type' => 'string']),
            new Property(['property' => 'displayName', 'type' => 'string']),
            new Property(['property' => 'fullName', 'type' => 'string']),
            new Property(['property' => 'photo', 'type' => 'string']),
            new Property(['property' => 'bio', 'type' => 'string']),
            new Property(['property' => 'birthday', 'type' => 'string']),
            new Property(['property' => 'sex', 'type' => 'string']),
            new Property(['property' => 'ideasAdded', 'type' => 'array', 'items' => new Items(['type' => 'string'])]),
            new Property(['property' => 'assignedWishes', 'type' => 'string']),
            new Property(['property' => 'systemNewsActive', 'type' => 'boolean']),
            new Property(['property' => 'newsActive', 'type' => 'boolean']),
            new Property(['property' => 'email', 'type' => 'string']),
            new Property(['property' => 'lastName', 'type' => 'string']),
            new Property(['property' => 'firstName', 'type' => 'string']),
            new Property(['property' => 'followersCount', 'type' => 'integer']),
            new Property(['property' => 'followingsCount', 'type' => 'integer']),
            new Property(['property' => 'wishesCount', 'type' => 'integer']),
            new Property(['property' => 'assignedWishesBySomebody', 'type' => 'integer']),
            new Property(['property' => 'socialProfiles', 'type' => 'array', 'items' => new Items(['type' => 'string'])])
        ];
    }

    /**
     * @return array|Property[]
     */
    public function getUserProperties(): array
    {
        return [
            new Property(['property' => '_id', 'type' => 'string']),
            new Property(['property' => 'username', 'type' => 'string']),
            new Property(['property' => 'displayName', 'type' => 'string']),
            new Property(['property' => 'fullName', 'type' => 'string']),
            new Property(['property' => 'photo', 'type' => 'string']),
            new Property(['property' => 'lastName', 'type' => 'string']),
            new Property(['property' => 'firstName', 'type' => 'string']),
            new Property(['property' => 'followersCount', 'type' => 'integer']),
            new Property(['property' => 'followingsCount', 'type' => 'integer']),
            new Property(['property' => 'wishesCount', 'type' => 'integer']),
            new Property(['property' => 'assignedWishesBySomebody', 'type' => 'integer']),
            new Property(['property' => 'followedByMe', 'type' => 'boolean']),
            new Property(['property' => 'favorite', 'type' => 'boolean']),
            new Property(['property' => 'socialProfiles', 'type' => 'array', 'items' => new Items(['type' => 'string'])])
        ];
    }
}