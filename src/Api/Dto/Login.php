<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     itemOperations = {},
 *     collectionOperations = {
 *          "post" = {
 *              "path" = "/auth/login.{_format}",
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
final class Login
{
    /**
     * @Groups({ "write" })
     *
     * @Assert\NotBlank
     * @Assert\Email
     *
     * @var string
     */
    public $email;

    /**
     * @Groups({ "write" })
     *
     * @Assert\NotBlank
     *
     * @var string
     */
    public $password;

    /**
     * @Groups({ "read" })
     *
     * @var string
     */
    public $token;
}