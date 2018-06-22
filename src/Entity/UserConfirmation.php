<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="userdata.confirmation")
 * @ORM\HasLifecycleCallbacks
 */
class UserConfirmation
{
    /**
     * @var User
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "User", inversedBy = "confirmation")
     * @ORM\JoinColumn(name = "user_id", referencedColumnName = "id")
     */
    protected $user;

    /**
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * @ORM\Column(type="string")
     */
    protected $email_confirmation_code;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $email_confirmed;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $email_confirmation_time;

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
     * @ORM\Column(type="string")
     */
    protected $password_recovery_code;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $password_recovery_time;

    public function __construct()
    {
        $this->email_confirmed = false;
    }

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

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;
        return $this;
    }

    public function getEmailConfirmationCode(): ?string
    {
        return $this->email_confirmation_code;
    }

    public function setEmailConfirmationCode(?string $code)
    {
        $this->email_confirmation_code = $code;
        return $this;
    }

    public function getEmailConfirmed(): bool
    {
        return $this->email_confirmed;
    }

    public function setEmailConfirmed(bool $isConfirmed)
    {
        $this->email_confirmed = $isConfirmed;
        return $this;
    }

    public function getEmailConfirmationTime(): ?\DateTime
    {
        return $this->email_confirmation_time;
    }

    public function setEmailConfirmationTime(\DateTime $time)
    {
        $this->email_confirmation_time = $time;
        return $this;
    }

    public function getPasswordRecoveryCode(): ?string
    {
        return $this->password_recovery_code;
    }

    public function setPasswordRecoveryCode(?string $code)
    {
        $this->password_recovery_code = $code;
        return $this;
    }

    public function getPasswordRecoveryTime(): ?\DateTime
    {
        return $this->password_recovery_time;
    }

    public function setPasswordRecoveryTime(\DateTime $time)
    {
        $this->password_recovery_time = $time;
        return $this;
    }
}