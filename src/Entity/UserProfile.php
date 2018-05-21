<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="userdata.profile")
 * @ORM\HasLifecycleCallbacks
 */
class UserProfile
{
    /**
     * @var User
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "User", inversedBy = "profile")
     * @ORM\JoinColumn(name = "user_id", referencedColumnName = "id")
     */
    protected $user;

    /**
     * @ORM\Column(type="integer")
     */
    protected $phone;

    /**
     * @ORM\Column(type="string")
     */
    protected $lastname;

    /**
     * @ORM\Column(type="string")
     */
    protected $firstname;

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

    public function getPhone(): ?int
    {
        return $this->phone;
    }

    public function setPhone(int $phone): void
    {
        $this->phone = $phone;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }

    public function getMtime(): \DateTime
    {
        return $this->mtime;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}