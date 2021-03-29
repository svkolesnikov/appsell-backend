<?php

namespace App\Entity\Repository;

use App\Entity\Offer;
use App\Entity\PromoCode;
use App\Entity\User;
use App\Lib\Enum\UserGroupEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

class PromoCodeRepository extends EntityRepository
{
    /**
     * @param Offer $offer
     *
     * @return PromoCode|null
     *
     * @throws NonUniqueResultException
     */
    public function getRandomFresh(Offer $offer): ?PromoCode
    {
        $q = $this->createQueryBuilder('q');

        return $q->where('q.status = :status')
            ->andWhere('q.offer = :offer')
            ->andWhere('q.user is null')
            ->setParameter(':status', PromoCode::STATUS_FRESH)
            ->setParameter(':offer', $offer)
            ->setMaxResults(1)
            ->orderBy('RANDOM()')
            ->getQuery()
            ->getOneOrNullResult();
    }
}