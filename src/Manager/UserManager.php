<?php

namespace App\Manager;

use App\Entity\User;
use App\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class UserManager
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var  UserGroupManager */
    protected $userManager;

    public function __construct(EntityManagerInterface $em, UserGroupManager $userGroupManager)
    {
        $this->entityManager = $em;
        $this->userManager = $userGroupManager;
    }

    public function getList(User $user, $criteria, $perPage, $offset): array
    {
        $qb = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        // "Работодатель" видит только своих сотрудников
        if ($this->userManager->hasGroup($user, UserGroupEnum::SELLER())) {
            $qb->innerJoin('u.profile', 'p')
                ->where('p.employer = :user')
                ->setParameter(':user', $user);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }
}