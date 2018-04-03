<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     itemOperations = {},
 *     collectionOperations = {
 *          "post" = {
 *              "path" = "/auth/token.{_format}"
 *          }
 *     }
 * )
 */
final class Token
{
    /**
     * @Assert\NotBlank
     *
     * @var string
     */
    public $token;
}