<?php

namespace App\Security;

use App\Entity\Group;
use App\Entity\User;
use App\Enum\UserGroupEnum;
use App\Exception\AppException;
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

    /**
     * @param User $user
     * @param UserGroupEnum $group
     * @throws AppException
     */
    public function addGroup(User $user, UserGroupEnum $group): void
    {
        /** @var Group $groupEntity */
        $groupEntity = $this->entityManager->getRepository('App:Group')->findOneBy(['code' => $group->getValue()]);
        if (null === $groupEntity) {
            throw new AppException(sprintf('Невозможно добавить пользователя в группу %s', $group->getValue()));
        }

        $user->addGroup($groupEntity);
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