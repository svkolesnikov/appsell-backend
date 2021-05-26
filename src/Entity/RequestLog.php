<?php

namespace App\Entity;

use App\Entity\Repository\RequestLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RequestLogRepository::class)
 * @ORM\Table(name="payments.request_log")
 */
class RequestLog
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
     * @ORM\Column(type="string", length=255)
     */
    private $message;

    /**
     * @ORM\Column(type="json")
     */
    private $request_data = [];

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $response_data = [];

    /**
     * @ORM\Column(type="datetime")
     */
    private $ctime;

    /**
     * @ORM\Column(type="datetime")
     */
    private $mtime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRqUid(): ?string
    {
        return $this->rquid;
    }

    public function setRqUid(string $rquid): self
    {
        $this->rquid = $rquid;

        return $this;
    }

    public function getRequestData(): ?array
    {
        return $this->request_data;
    }

    public function setRequestData(array $request_data): self
    {
        $this->request_data = $request_data;

        return $this;
    }

    public function getResponseData(): ?array
    {
        return $this->response_data;
    }

    public function setResponseData(?array $response_data): self
    {
        $this->response_data = $response_data;

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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
