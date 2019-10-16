<?php

namespace App\Entity\Repository;

use App\Entity\ImportFromCsvLogItem;
use Doctrine\ORM\EntityRepository;

class ImportFromCsvLogRepository extends EntityRepository
{
    /**
     * @param int $count
     * @return ImportFromCsvLogItem[]
     */
    public function getLastItems(int $count = 100)
    {
        return $this->createQueryBuilder('i')
            ->select('i')
            ->orderBy('i.id', 'desc')
            ->setMaxResults($count)
            ->getQuery()
            ->execute();
    }
}