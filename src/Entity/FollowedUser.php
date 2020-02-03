<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\Repository\FollowedUserRepository")
 * @ORM\Table(name="actiondata.followed_user")
 */
class FollowedUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "who_user_id", referencedColumnName = "id")
     */
    protected $who;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "whom_user_id", referencedColumnName = "id")
     */
    protected $whom;

    /**
     * @ORM\Column(type="integer")
     */
    protected $earned_amount;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $ctime;

    public function getId(): int
    {
        return $this->id;
    }

    public function getWho(): User
    {
        return $this->who;
    }

    public function getWhom(): User
    {
        return $this->whom;
    }

    public function getEarnedAmount(): int
    {
        return $this->earned_amount;
    }

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }
}