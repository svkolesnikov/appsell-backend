<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use App\ORM\Id\UuidGenerator;

/**
 * @ApiResource(
 *     collectionOperations = {},
 *     itemOperations = {
 *          "get" = {
 *              "access_control" = "object == group"
 *          }
 *     },
 *     attributes = {
 *          "normalization_context" = {"groups" = {"read"}}
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="userdata.group")
 * @ORM\HasLifecycleCallbacks
 */
class Group
{
    /**
     * @Groups({ "read" })
     *
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    protected $id;

    /**
     * @Groups({ "read" })
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @Groups({ "read" })
     *
     * @ORM\Column(type="text[]")
     */
    protected $roles;

    public function __construct($name = '', $roles = [])
    {
        $this->name = $name;
        $this->roles = $roles;
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

    public function getName()
    {
        return $this->name;
    }

    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->roles, true);
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function setName($name)
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
}