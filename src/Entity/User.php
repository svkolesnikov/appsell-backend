<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Lib\Orm\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="userdata.user")
 * @ORM\HasLifecycleCallbacks
 */
class User implements UserInterface, \Serializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * @ORM\Column(type="string")
     */
    protected $password;

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
     * @var UserProfile
     * @ORM\OneToOne(targetEntity = "UserProfile", mappedBy = "user", cascade={"persist", "remove"})
     */
    protected $profile;

    /**
     * @var UserConfirmation
     * @ORM\OneToOne(targetEntity = "UserConfirmation", mappedBy = "user", cascade={"persist", "remove"})
     */
    protected $confirmation;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_active;

    /**
     * @ORM\Column(type="string")
     */
    protected $token_salt;

    /**
     * @var Group[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Group")
     * @ORM\JoinTable(name="userdata.user2group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

    /**
     * @var SellerBaseCommission.php
     *
     * @ORM\OneToOne(targetEntity = "SellerBaseCommission", mappedBy = "seller", cascade={"persist", "remove"})
     */
    protected $sellerCommission;

    public function __construct()
    {
        $this->is_active = false;
        $this->groups    = new ArrayCollection();
    }

    /**
     * @return Group[]|ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    public function setGroups($groups)
    {
        $this->groups = $groups;
        return $this;
    }

    public function addGroup(Group $group)
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        foreach ($this->getGroups() as $group) {
            $roles = array_merge($roles, $group->getRoles());
        }

        return array_unique($roles);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password)
    {
        $this->password = $password;
        $this->renewTokenSalt();

        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): ?string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }

    public function serialize(): string
    {
        return json_encode([
            $this->id,
            $this->email,
            $this->password
        ]);
    }

    public function unserialize($serialized): void
    {
        [
            $this->id,
            $this->email,
            $this->password
        ] = json_decode($serialized, true);
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }

    public function getMtime(): \DateTime
    {
        return $this->mtime;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->ctime = new \DateTime();
        $this->mtime = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->mtime = new \DateTime();
    }

    public function getProfile(): UserProfile
    {
        return $this->profile ?? (new UserProfile())->setUser($this);
    }

    public function setProfile(UserProfile $profile): void
    {
        $this->profile = $profile;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function setActive(bool $active): void
    {
        $this->is_active = $active;
    }

    public function __toString()
    {
        return (string) $this->getUsername();
    }

    public function getSellerCommission(): ?SellerBaseCommission
    {
        return $this->sellerCommission;
    }

    public function setSellerCommission(SellerBaseCommission $sellerCommission)
    {
        $this->sellerCommission = $sellerCommission;
    }

    public function getConfirmation(): UserConfirmation
    {
        return $this->confirmation ?? (new UserConfirmation())->setUser($this);
    }

    public function setConfirmation(UserConfirmation $confirmation)
    {
        $this->confirmation = $confirmation;
        return $this;
    }

    public function getTokenSalt(): ?string
    {
        return $this->token_salt;
    }

    /**
     * Обновление соли для токена доступа –
     * все предыдущие токены доступа будут инвалидированы
     *
     * @return $this
     */
    public function renewTokenSalt(): self
    {
        $this->token_salt = substr(md5(mt_rand()), 5, 10);
        return $this;
    }
}