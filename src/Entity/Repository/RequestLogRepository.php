<?php

namespace App\Entity\Repository;

use App\Entity\RequestLog;
use Doctrine\ORM\EntityRepository;

/**
 * @method RequestLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method RequestLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method RequestLog[]    findAll()
 * @method RequestLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RequestLogRepository extends EntityRepository
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
