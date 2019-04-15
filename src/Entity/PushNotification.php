<?php

namespace App\Entity;

use App\Lib\Enum\ActionLogItemTypeEnum;
use App\Lib\Enum\PushNotificationStatusEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="actiondata.push_notifications")
 * @ORM\HasLifecycleCallbacks
 */
class PushNotification
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "sender_id", referencedColumnName = "id")
     */
    protected $sender;

    /**
     * @var Offer
     * @ORM\ManyToOne(targetEntity = "Offer")
     * @ORM\JoinColumn(name = "offer_id", referencedColumnName = "id")
     */
    protected $offer;

    /**
     * @ORM\Column(name="recipient_ids", type="text")
     */
    protected $recipients;

    /**
     * @ORM\Column(type="string")
     */
    protected $message;

    /**
     * @ORM\Column(type="text")
     */
    protected $data;

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
     * @var PushNotificationLog[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="PushNotificationLog", mappedBy="notification", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $logs;

    /**
     * @ORM\Column(type="string")
     */
    protected $status;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        $this->ctime = new \DateTime();
        $this->mtime = new \DateTime();
    }

    public function onPreUpdate(): void
    {
        $this->mtime = new \DateTime();
    }

    public function __construct()
    {
        $this->logs = new ArrayCollection();
        $this->status = PushNotificationStatusEnum::NEW;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(User $sender)
    {
        $this->sender = $sender;
        return $this;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function setOffer(?Offer $offer)
    {
        $this->offer = $offer;
        return $this;
    }

    public function getRecipients()
    {
        return $this->recipients;
    }

    public function setRecipients($recipients)
    {
        $this->recipients = $recipients;
        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
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

    /**
     * @return PushNotificationLog[]|ArrayCollection
     */
    public function getLogs()
    {
        return $this->logs;
    }

    public function setLogs($logs)
    {
        $this->logs = $logs;
    }

    public function addLog(PushNotificationLog $log)
    {
        if (!$this->logs->contains($log)) {
            $log->setNotification($this);
            $this->logs->add($log);
        }

        return $this;
    }

    public function removeLog(PushNotificationLog $log)
    {
        if ($this->logs->contains($log)) {
            $this->logs->removeElement($log);
        }

        return $this;
    }

    public function getStatus(): PushNotificationStatusEnum
    {
        return new PushNotificationStatusEnum($this->status);
    }

    public function setStatus(PushNotificationStatusEnum $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusTitle(): string
    {
        return PushNotificationStatusEnum::getTitleByValue($this->getStatus()->getValue());
    }
}