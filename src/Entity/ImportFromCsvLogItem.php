<?php

namespace App\Entity;

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
     * @ORM\Column(type="string")
     */
    protected $user_id;

    /**
     * @ORM\Column(type="string")
     */
    protected $click_id;

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
        string $userId,
        ?string $clickId,
        ?string $eventName,
        ?string $error,
        string $data
    )
    {
        $this->filename = $filename;
        $this->user_id = $userId;
        $this->click_id = $clickId;
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

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getClickId(): ?string
    {
        return $this->click_id;
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