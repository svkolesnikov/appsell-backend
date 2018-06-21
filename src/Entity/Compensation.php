<?php

namespace App\Entity;

use App\Lib\Enum\CompensationTypeEnum;
use App\Lib\Enum\CurrencyEnum;
use Doctrine\ORM\Mapping as ORM;
use App\Lib\Orm\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="offerdata.compensation")
 * @ORM\HasLifecycleCallbacks
 */
class Compensation
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
     * @ORM\ManyToOne(targetEntity = "Offer", inversedBy = "compensations")
     * @ORM\JoinColumn(name = "offer_id", referencedColumnName = "id")
     */
    protected $offer;

    /**
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @ORM\Column(type="string")
     */
    protected $description;

    /**
     * @ORM\Column(type="integer")
     */
    protected $price;

    /**
     * @ORM\Column(type="string")
     */
    protected $currency;

    /**
     * @var EventType
     * @ORM\ManyToOne(targetEntity = "EventType")
     * @ORM\JoinColumn(name = "event_type", referencedColumnName = "code")
     */
    protected $event_type;

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
        $this->type         = CompensationTypeEnum::BASE;
        $this->description  = '';
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

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function setOffer(Offer $offer)
    {
        $this->offer = $offer;
        return $this;
    }

    public function getTypeTitle()
    {
        return CompensationTypeEnum::getTitles()[$this->getType()->getValue()];
    }

    /**
     * @return CompensationTypeEnum
     * @throws \UnexpectedValueException
     */
    public function getType(): CompensationTypeEnum
    {
        return new CompensationTypeEnum($this->type);
    }

    public function setType(CompensationTypeEnum $type)
    {
        $this->type = $type->getValue();
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return CurrencyEnum
     * @throws \UnexpectedValueException
     */
    public function getCurrency(): CurrencyEnum
    {
        return new CurrencyEnum($this->currency);
    }

    public function setCurrency(CurrencyEnum $currency)
    {
        $this->currency = $currency->getValue();
        return $this;
    }

    public function getEventType(): ?EventType
    {
        return $this->event_type;
    }

    public function setEventType(?EventType $et)
    {
        $this->event_type = $et;
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