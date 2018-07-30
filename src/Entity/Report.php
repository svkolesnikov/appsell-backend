<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Lib\Orm\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="actiondata.report")
 * @ORM\HasLifecycleCallbacks
 */
class Report
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
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "user_id", referencedColumnName = "id")
     */
    protected $user;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $start_date;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $end_date;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $ctime;

    /**
     * @ORM\Column(type="text")
     */
    protected $data;

    public function __construct()
    {
        $this->data = json_encode([]);
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        $this->ctime = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): Report
    {
        $this->user = $user;
        return $this;
    }

    public function getStartDate(): \DateTime
    {
        return $this->start_date;
    }

    public function setStartDate(\DateTime $start_date): Report
    {
        $this->start_date = $start_date;
        return $this;
    }

    public function getEndDate(): \DateTime
    {
        return $this->end_date;
    }

    public function setEndDate(\DateTime $end_date): Report
    {
        $this->end_date = $end_date;
        return $this;
    }

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }

    public function setCtime(\DateTime $ctime): Report
    {
        $this->ctime = $ctime;
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
}