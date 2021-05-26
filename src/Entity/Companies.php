<?php

namespace App\Entity;

use App\Entity\Repository\CompaniesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CompaniesRepository::class)
 * @ORM\Table(name="payments.companies")
 */
class Companies
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $short_title;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTimeInterface $ctime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getShortTitle(): ?string
    {
        return $this->title;
    }

    public function setShortTitle(string $short_title): self
    {
        $this->short_title = $short_title;

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
