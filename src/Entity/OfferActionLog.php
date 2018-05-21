<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\ORM\Id\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="offerdata.offer_action_log")
 * @ORM\HasLifecycleCallbacks
 */
class OfferActionLog
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="seller_id", referencedColumnName="id")
     */
    protected $seller;

    /**
     * @var Offer
     * @ORM\ManyToOne(targetEntity = "Offer")
     * @ORM\JoinColumn(name = "offer_id", referencedColumnName = "id")
     */
    protected $offer;

    /**
     * @var OfferAction
     * @ORM\ManyToOne(targetEntity = "OfferAction")
     * @ORM\JoinColumn(name = "action_id", referencedColumnName = "id")
     */
    protected $action;

    /**
     * @ORM\Column(type="string")
     */
    protected $device_uuid;

    /**
     * @ORM\Column(type="string")
     */
    protected $device_info;

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

    public function getSeller(): User
    {
        return $this->seller;
    }

    public function setSeller(User $seller): void
    {
        $this->seller = $seller;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function setOffer(Offer $offer): void
    {
        $this->offer = $offer;
    }

    public function getAction(): OfferAction
    {
        return $this->action;
    }

    public function setAction(OfferAction $action): void
    {
        $this->action = $action;
    }

    public function getDeviceUuid(): string
    {
        return $this->device_uuid;
    }

    public function setDeviceUuid(string $uuid): void
    {
        $this->device_uuid = $uuid;
    }

    public function getDeviceInfo(): array
    {
        return $this->device_info
            ? json_decode($this->device_info)
            : [];
    }

    public function setDeviceInfo(?array $device_info): void
    {
        $this->device_info = \is_array($device_info)
            ? json_encode($device_info)
            : null;
    }

    /**
     * @return \DateTime
     */
    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }

    /**
     * @param \DateTime $ctime
     */
    public function setCtime(\DateTime $ctime): void
    {
        $this->ctime = $ctime;
    }

    /**
     * @return \DateTime
     */
    public function getMtime(): \DateTime
    {
        return $this->mtime;
    }

    /**
     * @param \DateTime $mtime
     */
    public function setMtime(\DateTime $mtime): void
    {
        $this->mtime = $mtime;
    }
}