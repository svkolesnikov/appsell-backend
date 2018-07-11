<?php

namespace App\Entity;

use App\Lib\Enum\OfferExecutionStatusEnum;
use Doctrine\ORM\Mapping as ORM;
use App\Lib\Orm\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="actiondata.offer_execution")
 * @ORM\HasLifecycleCallbacks
 */
class OfferExecution
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
     * @var OfferLink
     * @ORM\ManyToOne(targetEntity = "OfferLink")
     * @ORM\JoinColumn(name = "offer_link_id", referencedColumnName = "id")
     */
    protected $offer_link;

    /**
     * @var UserOfferLink
     * @ORM\ManyToOne(targetEntity = "UserOfferLink")
     * @ORM\JoinColumn(name = "source_link_id", referencedColumnName = "id")
     */
    protected $source_link;

    /**
     * @ORM\Column(type="string")
     */
    protected $source_referrer_info;

    /**
     * @ORM\Column(type="string")
     */
    protected $source_referrer_fingerprint;

    /**
     * @ORM\Column(type="string")
     */
    protected $status;

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


    public function __construct()
    {
        $this->source_referrer_info = json_encode([]);
        $this->status = OfferExecutionStatusEnum::PROCESSING;
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

    public function setOffer(Offer $offer)
    {
        $this->offer = $offer;
        return $this;
    }

    public function getOfferLink(): OfferLink
    {
        return $this->offer_link;
    }

    public function setOfferLink(OfferLink $link)
    {
        $this->offer_link = $link;
        return $this;
    }

    public function getSourceLink(): UserOfferLink
    {
        return $this->source_link;
    }

    public function setSourceLink(UserOfferLink $link)
    {
        $this->source_link = $link;
        return $this;
    }

    public function getSourceReferrerInfo(): array
    {
        return json_decode($this->source_referrer_info);
    }

    public function setSourceReferrerInfo(array $info)
    {
        $this->source_referrer_info = json_encode($info);
        return $this;
    }

    /**
     * @return OfferExecutionStatusEnum
     * @throws \UnexpectedValueException
     */
    public function getStatus(): OfferExecutionStatusEnum
    {
        return new OfferExecutionStatusEnum($this->status);
    }

    public function setStatus(OfferExecutionStatusEnum $status)
    {
        $this->status = $status->getValue();
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

    public function getSourceReferrerFingerprint(): ?string
    {
        return $this->source_referrer_fingerprint;
    }

    public function setSourceReferrerFingerprint(string $fingerprint)
    {
        $this->source_referrer_fingerprint = $fingerprint;
        return $this;
    }
}