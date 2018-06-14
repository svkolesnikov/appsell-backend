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
 *              "swagger_context" = {
 *                  "tags" = { "SDK" }
 *              }
 *          }
 *     },
 *     attributes = {
 *          "normalization_context" = {"groups" = {"read"}},
 *          "denormalization_context" = {"groups" = {"write"}}
 *     }
 * )
 */
final class SdkEvent
{
    /**
     * @Groups("write")
     * @Assert\NotBlank
     *
     * @var string
     */
    public $event_name;

    /**
     * @Groups("write")
     * @Assert\NotBlank
     *
     * @var string
     */
    public $offer_id;

    /**
     * @Groups("write")
     * @Assert\NotBlank
     *
     * @var string
     */
    public $offer_link_id;

    /**
     * @Groups("write")
     * @Assert\NotBlank
     *
     * @var string
     */
    public $device_id;
}