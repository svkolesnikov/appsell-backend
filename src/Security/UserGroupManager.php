<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\UserGroupEnum;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class UserGroupManager
{
    /** @var EntityManager */
    protected $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    public function addGroup(User $user, UserGroupEnum $group): void
    {
        // todo: Реализовать добавление группы к юзеру
    }

    public function hasGroup(User $user, UserGroupEnum $group): bool
    {
        foreach ($user->getGroups() as $ug) {
            if ($ug->getCode() === $group->getValue()) {
                return true;
            }
        }

        return false;
    }
}