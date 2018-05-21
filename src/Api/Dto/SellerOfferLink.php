<?php

namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     itemOperations = {
 *          "redirect_to_store" = {
 *              "method" = "GET",
 *              "path" = "/download-apps/{id}",
 *              "swagger_context" = {
 *                  "tags" = { "Offers" },
 *                  "responses" = {
 *                      "302" = { "description" = "Ссылка найдена, переход в стор к приложению" },
 *                      "404" = { "description" = "Resource not found" }
 *                  }
 *              }
 *          }
 *     },
 *     collectionOperations = {
 *          "create_link" = {
 *              "method" = "POST",
 *              "path" = "/offer-apps/{id}/sellers/{seller_id}/links",
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