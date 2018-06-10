<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\ORM\Id\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="userdata.group")
 * @ORM\HasLifecycleCallbacks
 */
class Group
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
    protected $name;

    /**
     * @ORM\Column(type="string")
     */
    protected $code;

    /**
     * @ORM\Column(type="text[]")
     */
    protected $roles;

    public function __construct()
    {
        $this->name  = '';
        $this->roles = [];
    }

    public function addRole($role)
    {
        if (!$this->hasRole($role)) {
            $this->roles[] = strtoupper($role);
        }

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasRole($role): bool
    {
        return \in_array(strtoupper($role), $this->roles, true);
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function removeRole(string $role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code)
    {
        $this->code = $code;
        return $this;
    }
}