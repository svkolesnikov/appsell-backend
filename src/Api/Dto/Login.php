<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     itemOperations = {},
 *     collectionOperations = {
 *          "post" = {
 *              "path" = "/auth/login.{_format}",
 *              "swagger_context" = {
 *                  "responses" = {
 *                      201 = {
 *                          "description" = "Пользователь успешно аутентифицирован и получен токен доступа",
 *                          "content" = {
 *                              "application/json" = {
 *                                  "schema" = {
 *                                      "type" = "object",
 *                                      "properties" = {
 *                                          "token" = { "type" = "string" }
 *                                      }
 *                                  }
 *                              }
 *                          }
 *                      },
 *                      400 = { "description" = "Invalid input" }
 *                  }
 *              }
 *          }
 *     }
 * )
 */
final class Login
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