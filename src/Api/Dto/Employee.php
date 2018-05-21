<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     itemOperations = {},
 *     collectionOperations = {
 *          "post" = {
 *              "path" = "/registration/employees.{_format}",
 *              "swagger_context" = {
 *                  "tags" = { "Registration" }
 *              }
 *          }
 *     },
 *     attributes = {
 *          "normalization_context" = {"groups" = {"read"}},
 *          "denormalization_context" = {"groups" = {"write"}}
 *     }
 * )
 */
final class Employee
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
     * @Groups({ "write" })
     *
     * @Assert\NotBlank
     *
     * @var string
     */
    public $code;
}