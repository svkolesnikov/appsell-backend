<?php

namespace App\Entity;

use App\Entity\Repository\PaymentsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PaymentsRepository::class)
 * @ORM\Table(name="payments.payments")
 */
class Payments
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $rquid;

    /**
     * @ORM\Column(type="datetime")
     */
    private $rqtm;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $member_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $company_id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $order_number;

    /**
     * @ORM\Column(type="datetime")
     */
    private $order_create_date;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $position_name;

    /**
     * @ORM\Column(type="integer")
     */
    private $position_count;

    /**
     * @ORM\Column(type="integer")
     */
    private $position_sum;

    /**
     * @ORM\Column(type="text")
     */
    private $position_description;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $id_qr;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $order_sum;

    /**
     * @ORM\Column(type="string", length=3)
     */
    private $currency;

    /**
     * @ORM\Column(type="text")
     */
    private $order_description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $order_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $order_from_url;

    /**
     * @ORM\Column(type="datetime")
     */
    private $ctime;

    /**
     * @ORM\Column(type="datetime")
     */
    private $mtime;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $promocode_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $seller_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRquid(): ?string
    {
        return $this->rquid;
    }

    public function setRquid(string $rquid): self
    {
        $this->rquid = $rquid;

        return $this;
    }

    public function getRqtm(): ?\DateTimeInterface
    {
        return $this->rqtm;
    }

    public function setRqtm(\DateTimeInterface $rqtm): self
    {
        $this->rqtm = $rqtm;

        return $this;
    }

    public function getCompanyId(): ?string
    {
        return $this->company_id;
    }

    public function setCompanyId(string $company_id): self
    {
        $this->company_id = $company_id;

        return $this;
    }

    public function getMemberId(): ?string
    {
        return $this->member_id;
    }

    public function setMemberId(string $member_id): self
    {
        $this->member_id = $member_id;

        return $this;
    }

    public function getOrderNumber(): ?string
    {
        return $this->order_number;
    }

    public function setOrderNumber(string $order_number): self
    {
        $this->order_number = $order_number;

        return $this;
    }

    public function getOrderCreateDate(): ?\DateTimeInterface
    {
        return $this->order_create_date;
    }

    public function setOrderCreateDate(\DateTimeInterface $order_create_date): self
    {
        $this->order_create_date = $order_create_date;

        return $this;
    }

    public function getPositionName(): ?string
    {
        return $this->position_name;
    }

    public function setPositionName(string $position_name): self
    {
        $this->position_name = $position_name;

        return $this;
    }

    public function getPositionCount(): ?int
    {
        return $this->position_count;
    }

    public function setPositionCount(int $position_count): self
    {
        $this->position_count = $position_count;

        return $this;
    }

    public function getPositionSum(): ?int
    {
        return $this->position_sum;
    }

    public function setPositionSum(int $position_sum): self
    {
        $this->position_sum = $position_sum;

        return $this;
    }

    public function getPositionDescription(): ?string
    {
        return $this->position_description;
    }

    public function setPositionDescription(string $position_description): self
    {
        $this->position_description = $position_description;

        return $this;
    }

    public function getIdQr(): ?string
    {
        return $this->id_qr;
    }

    public function setIdQr(string $id_qr): self
    {
        $this->id_qr = $id_qr;

        return $this;
    }

    public function getOrderSum(): ?string
    {
        return $this->order_sum;
    }

    public function setOrderSum(string $order_sum): self
    {
        $this->order_sum = $order_sum;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getOrderDescription(): ?string
    {
        return $this->order_description;
    }

    public function setOrderDescription(string $order_description): self
    {
        $this->order_description = $order_description;

        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->order_id;
    }

    public function setOrderId(?string $order_id): self
    {
        $this->order_id = $order_id;

        return $this;
    }

    public function getOrderFromUrl(): ?string
    {
        return $this->order_from_url;
    }

    public function setOrderFromUrl(?string $order_from_url): self
    {
        $this->order_from_url = $order_from_url;

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

    public function getPromocodeId(): ?string
    {
        return $this->promocode_id;
    }

    public function setPromocodeId(?string $promocode_id): self
    {
        $this->promocode_id = $promocode_id;

        return $this;
    }

    public function getSellerId(): ?string
    {
        return $this->seller_id;
    }

    public function setSellerId(string $seller_id): self
    {
        $this->seller_id = $seller_id;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
