<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use App\ORM\Id\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="offerdata.offer")
 * @ORM\HasLifecycleCallbacks
 */
class Offer
{
    /**
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
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @ORM\Column(type="string")
     */
    protected $description;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $active_from;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $active_to;

    /**
     * @ORM\Column(type="integer")
     */
    protected $price;

    /**
     * @ORM\Column(type="string")
     */
    protected $currency;

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
     * @var OfferAction[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="OfferAction", mappedBy="offer")
     */
    protected $actions;

    /**
     * @var OfferApp[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="OfferApp", mappedBy="offer")
     */
    protected $apps;

    public function __construct()
    {
        $this->actions = new ArrayCollection();
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

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
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
     * @return OfferAction[]|ArrayCollection
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return OfferApp[]|ArrayCollection
     */
    public function getApps()
    {
        return $this->apps;
    }
}