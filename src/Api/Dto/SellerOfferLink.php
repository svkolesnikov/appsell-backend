<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     itemOperations = {},
 *     collectionOperations = {
 *          "create_link" = {
 *              "method" = "POST",
 *              "path" = "/offer-apps/{id}/sellers/{seller_id}/links.{_format}",
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
final class SellerOfferLink
{
    /**
     * @Groups("read")
     *
     * @var string
     */
    public $url;
}