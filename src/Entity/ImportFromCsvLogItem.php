<?php

namespace App\Entity;

use App\Lib\Enum\OfferTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="actiondata.import_from_csv_log")
 * @ORM\HasLifecycleCallbacks
 */
class ImportFromCsvLogItem
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $filename;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "user_id", referencedColumnName = "id")
     */
    protected $user;

    /**
     * @var OfferExecution
     * @ORM\ManyToOne(targetEntity = "OfferExecution")
     * @ORM\JoinColumn(name = "click_id", referencedColumnName = "id")
     */
    protected $click;

    /**
     * @ORM\Column(type="string")
     */
    protected $event_name;

    /**
     * @ORM\Column(type="string")
     */
    protected $error;

    /**
     * @ORM\Column(type="string")
     */
    protected $data;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $ctime;

    public function __construct(
        string $filename,
        User $user,
        ?OfferExecution $click,
        ?string $eventName,
        ?string $error,
        string $data
    )
    {
        $this->filename = $filename;
        $this->user = $user;
        $this->click = $click;
        $this->event_name = $eventName;
        $this->error = $error;
        $this->data = $data;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        $this->ctime = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getClick(): ?OfferExecution
    {
        return $this->click;
    }

    public function getEventName(): ?string
    {
        return $this->event_name;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }
}