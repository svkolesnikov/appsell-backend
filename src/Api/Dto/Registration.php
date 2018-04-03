<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     itemOperations = {},
 *     collectionOperations = {
 *          "post" = {
 *              "path" = "/auth/registration.{_format}",
 *              "swagger_context" = {
 *                  "responses" = {
 *                      204 = { "description" = "Пользователь успешно зарегистрирован" },
 *                      400 = { "description" = "Invalid input" }
 *                  }
 *              }
 *          }
 *     }
 * )
 */
final class Registration
{
    /**
     * @Assert\NotBlank
     * @Assert\Email
     *
     * @var string
     */
    public $email;

    /**
     * @Assert\NotBlank
     *
     * @var string
     */
    public $password;
}