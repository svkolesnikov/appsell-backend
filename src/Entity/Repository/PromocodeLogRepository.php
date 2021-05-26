<?php

namespace App\Entity\Repository;

use App\Entity\PromocodeLog;
use Doctrine\ORM\EntityRepository;

/**
 * @method PromocodeLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method PromocodeLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method PromocodeLog[]    findAll()
 * @method PromocodeLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromocodeLogRepository extends EntityRepository
{
    public function check(string $rquid): bool
    {
        $search = $this->createQueryBuilder('request_log')
            ->andWhere('request_log.rquid = :rquid')
            ->setParameter('rquid', $rquid)
            ->getQuery()
            ->getResult();

        return (count($search) === 0);
    }
}
