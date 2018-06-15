<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     itemOperations = {},
 *     collectionOperations = {
 *          "post" = {
 *              "path" = "/auth/token.{_format}",
 *              "swagger_context" = {
 *                  "tags" = { "Auth" }
 *              }
 *          }
 *     },
 *     attributes = {
 *          "normalization_context" = {"groups" = {"read"}},
 *          "denormalization_context" = {"groups" = {"write"}}
 *     }
 * )
 */
final class Token
{
    /**
     * @Groups({ "read" })
     *
     * @var string
     */
    public $token;

    /**
     * @Groups({ "read" })
     *
     * @var string
     */
    public $user_id;
}