<?php

namespace App\Entity\Repository;

use App\Entity\OfferExecution;
use App\Entity;
use App\Lib\Enum\OfferExecutionStatusEnum;
use Doctrine\ORM\EntityRepository;

class OfferExecutionRepository extends EntityRepository
{
    /**
     * @param Entity\User $employee
     * @return array|OfferExecution[]
     */
    public function getPayoutAvailable(Entity\User $employee): array
    {
        return $this->createQueryBuilder('e')
            ->select('e')
            ->join('e.source_link', 'ul')
            ->where('ul.user = :user and e.status = :status and e.payout_transaction is null')
            ->setParameters([
                'user' => $employee,
                'status' => OfferExecutionStatusEnum::COMPLETE
            ])
            ->getQuery()
            ->execute();
    }

    public function getPayoutAvailableAmount(Entity\User $employee): int
    {
        $executions = $this->getPayoutAvailable($employee);
        return $this->getAmountFor($executions);
    }

    /**
     * Рассчет суммы компенсации для переданных исполнений
     *
     * @param array|OfferExecution[] $executions
     * @return int
     */
    public function getAmountFor(array $executions): int
    {
        $amount = array_reduce($executions, function ($amount, Entity\OfferExecution $e) {

            $eventAmount = $e->getEvents()->map(function (Entity\SdkEvent $event) {
                return $event->getAmountForEmployee();
            })->toArray();

            return $amount + array_sum($eventAmount);

        }, 0);

        // Сумма для выплаты должна быть в рублях, целым числом
        // округляется в менбшую сторону

        return floor($amount);
    }
}