<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Lib\Orm\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="offerdata.seller_approved_offer")
 * @ORM\HasLifecycleCallbacks
 */
class SellerApprovedOffer
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
     * @ORM\ManyToOne(targetEntity = "Offer", inversedBy="seller_approvals")
     * @ORM\JoinColumn(name = "offer_id", referencedColumnName = "id")
     */
    protected $offer;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "seller_id", referencedColumnName = "id")
     */
    protected $seller;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $ctime;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        $this->ctime = new \DateTime();
    }

    public function getId(): int
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

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }

    public function getSeller(): User
    {
        return $this->seller;
    }

    public function setSeller(User $seller)
    {
        $this->seller = $seller;
        return $this;
    }
}