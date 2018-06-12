<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     itemOperations = {},
 *     collectionOperations = {
 *          "post" = {
 *              "swagger_context" = {
 *                  "tags" = { "Offers" }
 *              }
 *          }
 *     },
 *     attributes = {
 *          "normalization_context" = {"groups" = {"read"}},
 *          "denormalization_context" = {"groups" = {"write"}}
 *     }
 * )
 */
final class UserOfferLink
{
    /**
     * @Groups("write")
     *
     * @var string
     */
    public $offer_id;

    /**
     * @Groups("write")
     *
     * @var string
     */
    public $user_id;

    /**
     * @Groups("read")
     *
     * @var string
     */
    public $url;
}