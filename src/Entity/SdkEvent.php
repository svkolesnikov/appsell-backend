<?php

namespace App\Entity;

use App\Lib\Enum\CurrencyEnum;
use App\Lib\Enum\SdkEventSourceEnum;
use Doctrine\ORM\Mapping as ORM;
use App\Lib\Orm\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="actiondata.sdk_event")
 * @ORM\HasLifecycleCallbacks
 */
class SdkEvent
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    protected $id;

    /**
     * @var EventType
     * @ORM\ManyToOne(targetEntity = "EventType")
     * @ORM\JoinColumn(name = "event_type", referencedColumnName = "code")
     */
    protected $event_type;

    /**
     * @var OfferExecution
     * @ORM\ManyToOne(targetEntity = "OfferExecution", inversedBy = "events")
     * @ORM\JoinColumn(name = "offer_execution_id", referencedColumnName = "id")
     */
    protected $offer_execution;

    /**
     * @ORM\Column(type="string")
     */
    protected $device_id;

    /**
     * @ORM\Column(type="string")
     */
    protected $source_info;

    /**
     * @ORM\Column(type="string")
     */
    protected $currency;

    /**
     * @ORM\Column(type="decimal")
     */
    protected $amount_for_service;

    /**
     * @ORM\Column(type="decimal")
     */
    protected $amount_for_seller;

    /**
     * @ORM\Column(type="decimal")
     */
    protected $amount_for_employee;

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
     * @var User
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "employee_id", referencedColumnName = "id")
     */
    protected $employee;

    /**
     * @ORM\Column(type="string")
     */
    protected $source;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $ctime;

    public function __construct()
    {
        $this->source_info = json_encode([]);
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        $this->ctime = new \DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEventType(): EventType
    {
        return $this->event_type;
    }

    public function setEventType(EventType $type)
    {
        $this->event_type = $type;
        return $this;
    }

    public function getOfferExecution(): OfferExecution
    {
        return $this->offer_execution;
    }

    public function setOfferExecution(OfferExecution $execution)
    {
        $this->offer_execution = $execution;
        return $this;
    }

    public function getDeviceId(): string
    {
        return $this->device_id;
    }

    public function setDeviceId(string $device_id)
    {
        $this->device_id = $device_id;
        return $this;
    }

    public function getSourceInfo(): array
    {
        return json_decode($this->source_info, true);
    }

    public function setSourceInfo(array $source_info)
    {
        $this->source_info = json_encode($source_info);
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

    /**
     * @return SdkEventSourceEnum
     * @throws \UnexpectedValueException
     */
    public function getSource(): SdkEventSourceEnum
    {
        return new SdkEventSourceEnum($this->source);
    }

    public function setSource(SdkEventSourceEnum $source)
    {
        $this->source = $source->getValue();
        return $this;
    }

    public function getAmountForService(): ?float
    {
        return $this->amount_for_service;
    }

    public function setAmountForService(float $amount)
    {
        $this->amount_for_service = $amount;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAmountForSeller(): ?float
    {
        return $this->amount_for_seller;
    }

    public function setAmountForSeller(float $amount)
    {
        $this->amount_for_seller = $amount;
        return $this;
    }

    public function getAmountForEmployee(): ?float
    {
        return $this->amount_for_employee;
    }

    public function setAmountForEmployee(float $amount)
    {
        $this->amount_for_employee = $amount;
        return $this;
    }

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }

    public function setCtime(\DateTime $ctime)
    {
        $this->ctime = $ctime;
        return $this;
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

    public function getEmployee(): ?User
    {
        return $this->employee;
    }

    public function setEmployee(?User $employee)
    {
        $this->employee = $employee;
        return $this;
    }
}