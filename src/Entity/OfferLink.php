<?php

namespace App\Entity;

use App\Enum\OfferLinkTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use App\ORM\Id\UuidGenerator;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     itemOperations = {
 *          "get" = {
 *              "swagger_context" = {
 *                  "tags" = { "Offers" }
 *              }
 *          }
 *     },
 *     collectionOperations = {},
 *     attributes = {
 *          "normalization_context" = {"groups" = {"read"}}
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="offerdata.offer_app")
 * @ORM\HasLifecycleCallbacks
 */
class OfferLink
{
    /**
     * @Groups({ "read" })
     *
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    protected $id;

    /**
     * @var Offer
     * @ORM\ManyToOne(targetEntity = "Offer", inversedBy = "links")
     * @ORM\JoinColumn(name = "offer_id", referencedColumnName = "id")
     */
    protected $offer;

    /**
     * @Groups({ "read" })
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @Groups({ "read" })
     *
     * @ORM\Column(type="string")
     */
    protected $url;

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
     * Идентификатор приложения в AppStore или GooglePlay
     *
     * @ORM\Column(type="string")
     */
    protected $external_id;

    public function __construct()
    {
        $this->type = OfferLinkTypeEnum::WEB;
        $this->url  = '';
    }

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

    public function getId(): string
    {
        return $this->id;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function setOffer(Offer $offer): void
    {
        $this->offer = $offer;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }

    public function getMtime(): \DateTime
    {
        return $this->mtime;
    }

    /**
     * @return OfferLinkTypeEnum
     * @throws \UnexpectedValueException
     */
    public function getType(): OfferLinkTypeEnum
    {
        return new OfferLinkTypeEnum($this->type);
    }

    public function setType(OfferLinkTypeEnum $type)
    {
        $this->type = $type->getValue();
        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    public function setExternalId(?string $id)
    {
        $this->external_id = $id;
        return $this;
    }
}