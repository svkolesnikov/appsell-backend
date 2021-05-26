<?php

namespace App\Entity\Repository;

use App\Entity\User;
use App\Lib\Enum\UserGroupEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    /**
     * @return array|User[]
     */
    public function findEmployeers(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u, p')
            ->from(User::class, 'u')
            ->innerJoin('u.groups', 'g')
            ->innerJoin('u.profile', 'p')
            ->where('g.code = :code and p.company_payout_over_solar_staff = false')
            ->orderBy('p.company_title', 'asc')
            ->setParameter(':code', UserGroupEnum::SELLER)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $seller_id
     *
     * @return bool
     */
    public function checkSeller(string $seller_id): bool
    {
	return strlen($seller_id) === 36 && is_object($this->findOneBy(['id' => $seller_id]));
    }

    /**
     * @return array|User[]
     */
    public function findSelers(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->innerJoin('u.groups', 'g')
            ->where('g.code = :code')
            ->setParameter(':code', UserGroupEnum::SELLER)
            ->getQuery()
            ->getResult();
    }
}