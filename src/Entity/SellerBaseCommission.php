<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="financedata.seller_base_commission")
 * @ORM\HasLifecycleCallbacks
 */
class SellerBaseCommission
{
    /**
     * @var User
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "seller_id", referencedColumnName = "id")
     */
    protected $seller;

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

    public function getSeller(): User
    {
        return $this->seller;
    }

    public function setSeller(User $seller)
    {
        $this->seller = $seller;
        return $this;
    }

    public function getPercent(): int
    {
        return $this->percent;
    }

    public function setPercent($percent)
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