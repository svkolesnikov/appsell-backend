<?php

namespace App\Entity;

use App\Entity\Repository\PromocodeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PromocodeRepository::class)
 * @ORM\Table(name="payments.promocode")
 */
class Promocode
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;
    /**
     * @ORM\Column(type="integer")
     */
    private int $company_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $code;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $seller_id;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTimeInterface $received;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $given;

    /**
     * @ORM\Column(type="integer")
     */
    private int $usage_status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ctime;

    /**
     * @ORM\Column(type="datetime")
     */
    private  $mtime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCompanyId(): ?int
    {
        return $this->company_id;
    }

    public function setCompanyId(int $company_id): self
    {
        $this->company_id = $company_id;

        return $this;
    }

    public function getSellerId(): ?string
    {
        return $this->seller_id;
    }

    public function setSellerId(?string $seller_id): self
    {
        $this->seller_id = $seller_id;

        return $this;
    }

    public function getReceived(): ?\DateTimeInterface
    {
        return $this->received;
    }

    public function setReceived(\DateTimeInterface $received): self
    {
        $this->received = $received;

        return $this;
    }

    public function getGiven(): ?\DateTimeInterface
    {
        return $this->given;
    }

    public function setGiven(?\DateTimeInterface $given): self
    {
        $this->given = $given;

        return $this;
    }

    public function getUsageStatus(): ?int
    {
        return $this->usage_status;
    }

    public function setUsageStatus(int $usage_status): self
    {
        $this->usage_status = $usage_status;

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

    public function getMtime(): ?\DateTimeInterface
    {
        return $this->mtime;
    }

    public function setMtime(\DateTimeInterface $mtime): self
    {
        $this->mtime = $mtime;

        return $this;
    }
}
