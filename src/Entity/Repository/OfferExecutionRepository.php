<?php

namespace App\Entity\Repository;

use App\Entity\OfferExecution;
use App\Entity\User;
use App\Lib\Enum\OfferExecutionStatusEnum;
use Doctrine\ORM\EntityRepository;

class OfferExecutionRepository extends EntityRepository
{
    /**
     * @param User $employee
     * @return array|OfferExecution[]
     */
    public function getPayoutAvailable(User $employee): array
    {
        return $this->createQueryBuilder('oe')
            ->select('e')
            ->from('App:OfferExecution', 'e')
            ->join('e.source_link', 'ul')
            ->where('ul.user = :user and e.status = :status and e.payout_transaction is null')
            ->setParameters([
                'user' => $employee,
                'status' => OfferExecutionStatusEnum::COMPLETE
            ])
            ->getQuery()
            ->execute();
    }
}