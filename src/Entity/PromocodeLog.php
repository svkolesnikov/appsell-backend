<?php

namespace App\Entity;

use App\Entity\Repository\PromocodeLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PromocodeLogRepository::class)
 * @ORM\Table(name="payments.promocode_log")
 */
class PromocodeLog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ctime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

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
