<?php

namespace App\Entity\Repository;

use App\Entity\Promocode;
use Doctrine\ORM\EntityRepository;

/**
 * @method Promocode|null find($id, $lockMode = null, $lockVersion = null)
 * @method Promocode|null findOneBy(array $criteria, array $orderBy = null)
 * @method Promocode[]    findAll()
 * @method Promocode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromocodeRepository extends EntityRepository
{
    public function add(string $company_id, array $promo)
    {
        foreach ($promo as $code) {
            $date      = new \DateTime();
            $promocode = new Promocode();
            $promocode->setCode($code);
            $promocode->setCompanyId($company_id ?? 1);
            $promocode->setReceived($date);
            $promocode->setUsageStatus(0);
            $promocode->setCtime($date);
            $promocode->setMtime($date);

            $this->getEntityManager()->persist($promocode);
            $this->getEntityManager()->flush($promocode);

            unset($promocode);
        }
    }

    public function markUsed(string $company_id, string $promocode): bool
    {
        $code = $this->createQueryBuilder('promocode')
            ->andWhere('promocode.code = :code')
            ->andWhere('promocode.company_id = :company_id')
            ->andWhere('promocode.seller_id is not NULL')
	    ->andWhere('promocode.usage_status = :usage_status')
            ->setParameter('code', $promocode)
            ->setParameter('company_id', $company_id)
	    ->setParameter('usage_status', 0)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()[0];

        if ($code !== null) {
            $code->setUsageStatus(1);
            $code->setMtime(new \DateTime());
            $this->getEntityManager()->flush($code);
        }

        return $code !== null;
    }

    public function getOne(string $seller, string $company_id)
    {
        $promocode = $this->findFreeOne($company_id);
        
        if ($promocode !== null) {
            $promocode->setSellerId($seller);
            $promocode->setGiven(new \DateTime());
            $this->getEntityManager()->flush($promocode);
        }

        return ($promocode !== null) ? $promocode : false;
    }

    public function findFreeOne(string $company_id)
    {
        return $this->createQueryBuilder('promocode')
            ->andWhere('promocode.given is NULL')
            ->andWhere('promocode.company_id = :company_id')
            ->setParameter('company_id', $company_id)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()[0];
    }

    public function findNotUsagePromocodes()
    {
        return $this->createQueryBuilder('promocode')
            ->andWhere('promocode.usage_status = :usage_status')
            ->andWhere('promocode.given is not NULL')
	    ->andWhere('promocode.given < :time')
            ->setParameter('usage_status', 0)
	    ->setParameter('time', gmdate('Y-m-d H:i:s', time() - 10800))
            ->getQuery()
            ->getResult();
    }

    public function setNotUsage(Promocode $promocode)
    {
        $promocode->setSellerId(null);
        $promocode->setUsageStatus(0);
        $promocode->setGiven(null);

        $this->getEntityManager()->flush($promocode);
    }
}
