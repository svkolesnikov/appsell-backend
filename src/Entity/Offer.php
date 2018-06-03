<?php

namespace App\Entity;

use App\Enum\CurrencyEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use App\ORM\Id\UuidGenerator;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiSubresource;

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
     * @Groups({ "read" })
     *
     * @ORM\Column(type="integer")
     */
    protected $price;

    /**
     * @Groups({ "read" })
     *
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
     * @Groups({ "read" })
     *
     * @var OfferAction[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="OfferAction", mappedBy="offer", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $actions;

    /**
     * @Groups({ "read" })
     *
     * @var OfferApp[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="OfferApp", mappedBy="offer", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $apps;

    public function __construct()
    {
        $this->actions      = new ArrayCollection();
        $this->apps         = new ArrayCollection();
        $this->title        = '';
        $this->active_from  = new \DateTime();
        $this->active_to    = new \DateTime();
        $this->price        = 0;
        $this->currency     = CurrencyEnum::RUB;
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

    /**
     * @throws \UnexpectedValueException
     */
    public function getCurrency(): CurrencyEnum
    {
        return new CurrencyEnum($this->currency);
    }

    public function setCurrency(CurrencyEnum $currency): void
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

    public function setActions($actions)
    {
        $this->actions = $actions;
        return $this;
    }

    public function addAction(OfferAction $action)
    {
        $action->setOffer($this);
        $this->actions->add($action);

        return $this;
    }

    public function removeAction(OfferAction $action)
    {
        $action->setOffer(null);
        $this->actions->removeElement($action);

        return $this;
    }

    /**
     * @return OfferApp[]|ArrayCollection
     */
    public function getApps()
    {
        return $this->apps;
    }

    public function setApps($apps)
    {
        $this->apps = $apps;
        return $this;
    }

    public function addApp(OfferApp $app)
    {
        $app->setOffer($this);
        $this->apps->add($app);

        return $this;
    }

    public function removeApp(OfferApp $app)
    {
        $app->setOffer(null);
        $this->apps->removeElement($app);

        return $this;
    }
}