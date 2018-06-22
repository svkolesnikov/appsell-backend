<?php

namespace App\Entity;

use App\Lib\Enum\CommissionEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="financedata.base_commission")
 * @ORM\HasLifecycleCallbacks
 */
class BaseCommission
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $type;

    /**
     * @ORM\Column(type="string")
     */
    protected $description;

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
     * @ORM\Column(type="integer")
     */
    protected $percent;

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

    /**
     * @return CommissionEnum
     * @throws \UnexpectedValueException
     */
    public function getType(): CommissionEnum
    {
        return new CommissionEnum($this->type);
    }

    public function setType(CommissionEnum $type)
    {
        $this->type = $type->getValue();
        return $this;
    }

    public function getTypeTitle()
    {
        return CommissionEnum::getTitles()[$this->type];
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
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

    public function getPercent(): int
    {
        return $this->percent;
    }

    public function setPercent(int $percent)
    {
        $this->percent = $percent;
        return $this;
    }
}