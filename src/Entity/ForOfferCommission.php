<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Lib\Orm\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="financedata.for_offer_commission")
 * @ORM\HasLifecycleCallbacks
 */
class ForOfferCommission
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    protected $id;

    /**
     * @var Offer
     * @ORM\ManyToOne(targetEntity = "Offer")
     * @ORM\JoinColumn(name = "offer_id", referencedColumnName = "id")
     */
    protected $offer;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "by_user_id", referencedColumnName = "id")
     */
    protected $by_user;

    /**
     * @ORM\Column(type="integer")
     */
    protected $percent;

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

    public function getId(): string
    {
        return $this->id;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function setOffer(Offer $offer)
    {
        $this->offer = $offer;
        return $this;
    }

    public function getByUser(): ?User
    {
        return $this->by_user;
    }

    public function setByUser(?User $by_user)
    {
        $this->by_user = $by_user;
        return $this;
    }

    public function getPercent(): int
    {
        return $this->percent;
    }

    public function setPercent(int $percent)
    {
        $this->percent = $percent;
        return $this;
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