<?php

namespace App\Entity;

use App\Entity\Repository\MembersRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MembersRepository::class)
 * @ORM\Table(name="payments.members")
 */
class Members
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $payment_id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ctime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaymentId(): ?string
    {
        return $this->payment_id;
    }

    public function setPaymentId(string $payment_id): self
    {
        $this->payment_id = $payment_id;

        return $this;
    }
    public function getCtime(): ?\DateTimeInterface
    {
        return $this->ctime;
    }

    public function setCtime(\DateTimeInterface $ctime): self
    {
        $this->ctime = $ctime;

        return $this;
    }
}
