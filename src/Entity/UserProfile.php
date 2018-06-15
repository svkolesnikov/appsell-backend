<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     collectionOperations = {},
 *     itemOperations = {
 *          "get" = {
 *              "access_control" = "object.user == user",
 *              "swagger_context" = {
 *                  "tags" = { "Users" }
 *              }
 *          }
 *     },
 *     attributes = {
 *          "normalization_context" = {"groups" = {"read"}}
 *     }
 * )
 *
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
     * @Groups({ "read" })
     *
     * @ORM\Column(type="integer")
     */
    protected $phone;

    /**
     * @Groups({ "read" })
     *
     * @ORM\Column(type="string")
     */
    protected $lastname;

    /**
     * @Groups({ "read" })
     *
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
     * @Groups({ "read" })
     *
     * @ORM\Column(type="string")
     */
    public $company_id;

    /**
     * @ORM\Column(type="string")
     */
    protected $company_title;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "employer_id", referencedColumnName = "id")
     */
    protected $employer;

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

    public function getCompanyId(): ?string
    {
        return $this->company_id;
    }

    public function setCompanyId(?string $id)
    {
        $this->company_id = $id;
        return $this;
    }

    public function getEmployer(): ?User
    {
        return $this->employer;
    }

    public function setEmployer(?User $user)
    {
        $this->employer = $user;
        return $this;
    }

    public function getCompanyTitle(): ?string
    {
        return $this->company_title;
    }

    public function setCompanyTitle($title)
    {
        $this->company_title = $title;
        return $this;
    }

    //
    // Далее идут поля для API,
    // они никак не относятся к полям в БД
    //

    /**
     * Информация о компании продавца
     *
     * @Groups({ "read" })
     * @var string
     */
    public $company_name;

    public function getCompanyName(): string
    {
        return null !== $this->getEmployer()
            ? $this->getEmployer()->getProfile()->getCompanyTitle()
            : $this->company_title;
    }
}