<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\Api\RedirectToStoreController;

/**
 * @ApiResource(
 *     itemOperations = {
 *          "redirect" = {
 *              "method" = "GET",
 *              "path" = "/seller-offer-links/{id}/go.{_format}",
 *              "controller" = RedirectToStoreController::class,
 *              "swagger_context" = {
 *                  "tags" = { "Offers" },
 *                  "responses" = {
 *                      "302" = { "description" = "Ссылка найдена, переход в стор к приложению" },
 *                      "404" = { "description" = "Resource not found" }
 *                  }
 *              }
 *          }
 *     },
 *     collectionOperations = {}
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="offerdata.seller_offer_link")
 * @ORM\HasLifecycleCallbacks
 */
class SellerOfferLink
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var OfferApp
     * @ORM\ManyToOne(targetEntity="OfferApp")
     * @ORM\JoinColumn(name="offer_app_id", referencedColumnName="id")
     */
    protected $offer_app;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="seller_id", referencedColumnName="id")
     */
    protected $seller;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $ctime;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $mtime;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        $this->ctime = new \DateTime();
        $this->mtime = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(): void
    {
        $this->mtime = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOfferApp(): OfferApp
    {
        return $this->offer_app;
    }

    public function setOfferApp(OfferApp $app): void
    {
        $this->offer_app = $app;
    }

    public function getSeller(): User
    {
        return $this->seller;
    }

    public function setSeller(User $seller): void
    {
        $this->seller = $seller;
    }

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }

    public function getMtime(): \DateTime
    {
        return $this->mtime;
    }
}