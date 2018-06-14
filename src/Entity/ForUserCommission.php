<?php

namespace App\Entity;

use App\Enum\CommissionEnum;
use App\Enum\OfferLinkTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use App\ORM\Id\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="financedata.for_user_commission")
 * @ORM\HasLifecycleCallbacks
 */
class ForUserCommission
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
     * @ORM\Column(type="string")
     */
    protected $by;

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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return CommissionEnum
     * @throws \UnexpectedValueException
     */
    public function getBy(): CommissionEnum
    {
        return new CommissionEnum($this->by);
    }

    public function setBy(CommissionEnum $by)
    {
        $this->by = $by->getValue();
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