<?php

namespace App\Entity;

use App\Enum\OfferTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
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
 *     collectionOperations = {
 *          "get" = {
 *              "swagger_context" = {
 *                  "tags" = { "Offers" }
 *              }
 *          }
 *     },
 *     attributes = {
 *          "normalization_context" = {"groups" = {"read"}}
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="offerdata.offer")
 * @ORM\HasLifecycleCallbacks
 */
class Offer
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
     * @var User
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "owner_id", referencedColumnName = "id")
     */
    protected $owner;

    /**
     * @Groups({ "read" })
     *
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @Groups({ "read" })
     *
     * @ORM\Column(type="string")
     */
    protected $description;

    /**
     * @Groups({ "read" })
     *
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $active_from;

    /**
     * @Groups({ "read" })
     *
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $active_to;

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
     * @Groups({ "read" })
     *
     * @var Compensation[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="Compensation", mappedBy="offer", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $compensations;

    /**
     * @Groups({ "read" })
     *
     * @var OfferLink[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="OfferLink", mappedBy="offer", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $links;

    /**
     * @Groups({ "read" })
     *
     * @var SellerApprovedOffer[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="SellerApprovedOffer", mappedBy="offer", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $seller_approvals;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_active;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_deleted;

    /**
     * @ORM\Column(type="string")
     */
    protected $type;

    public function __construct()
    {
        $this->compensations = new ArrayCollection();
        $this->links         = new ArrayCollection();
        $this->title         = '';
        $this->active_from   = new \DateTime();
        $this->active_to     = new \DateTime();
        $this->is_active     = false;
        $this->is_deleted    = false;
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

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getActiveFrom(): \DateTime
    {
        return $this->active_from;
    }

    public function setActiveFrom(\DateTime $date): void
    {
        $this->active_from = $date;
    }

    public function getActiveTo(): \DateTime
    {
        return $this->active_to;
    }

    public function setActiveTo(\DateTime $date): void
    {
        $this->active_to = $date;
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
     * @return Compensation[]|ArrayCollection
     */
    public function getCompensations()
    {
        return $this->compensations;
    }

    public function setCompensations($compensations)
    {
        $this->compensations = $compensations;
        return $this;
    }

    public function addCompensation(Compensation $compensation)
    {
        if (!$this->compensations->contains($compensation)) {
            $compensation->setOffer($this);
            $this->compensations->add($compensation);
        }

        return $this;
    }

    public function removeCompensation(Compensation $compensation)
    {
        if (!$this->compensations->contains($compensation)) {
            $this->compensations->removeElement($compensation);
        }

        return $this;
    }

    /**
     * @return OfferLink[]|ArrayCollection
     */
    public function getLinks()
    {
        return $this->links;
    }

    public function setLinks($links)
    {
        $this->links = $links;
        return $this;
    }

    public function addLink(OfferLink $link)
    {
        if (!$this->links->contains($link)) {
            $link->setOffer($this);
            $this->links->add($link);
        }

        return $this;
    }

    public function removeLink(OfferLink $link)
    {
        if (!$this->links->contains($link)) {
            $this->links->removeElement($link);
        }

        return $this;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function setActive(bool $active)
    {
        $this->is_active = $active;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->is_deleted;
    }

    public function setDeleted(bool $deleted)
    {
        $this->is_deleted = $deleted;
        return $this;
    }

    public function getSellerApprovals()
    {
        return $this->seller_approvals;
    }

    /**
     * @return OfferTypeEnum
     * @throws \UnexpectedValueException
     */
    public function getType(): OfferTypeEnum
    {
        return new OfferTypeEnum($this->type);
    }

    public function setType(OfferTypeEnum $type)
    {
        $this->type = $type;
        return $this;
    }
}