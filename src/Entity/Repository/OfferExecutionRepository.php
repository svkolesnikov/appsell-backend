<?php

namespace App\Entity\Repository;

use App\Entity\OfferExecution;
use App\Entity;
use App\Lib\Enum\OfferExecutionStatusEnum;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityRepository;
use Ramsey\Uuid\Uuid;

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
            ->setParameters(
                [
                    'user' => $employee,
                    'status' => OfferExecutionStatusEnum::COMPLETE
                ]
            )
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
        $amount = array_reduce(
            $executions,
            function ($amount, Entity\OfferExecution $e) {
                $eventAmount = $e->getEvents()->map(
                    function (Entity\SdkEvent $event) {
                        return $event->getAmountForEmployee();
                    }
                )->toArray();

                return $amount + array_sum($eventAmount);
            },
            0
        );

        // Сумма для выплаты должна быть в рублях, целым числом
        // округляется в менбшую сторону

        return floor($amount);
    }

    public function getClickStats(array $filter): array
    {
        if (!isset($filter['date_from']) || !isset($filter['date_to'])) {
            return [];
        }

        $connection = $this->_em->getConnection();
        $condition = '';
        $conditions = [];

        if (isset($filter['date_from'], $filter['date_to'])) {
            $conditions[] = sprintf(
                " click.ctime between '%s 00:00:00' and '%s 23:59:59'",
                $filter['date_from']->format('Y-m-d'),
                $filter['date_to']->format('Y-m-d'),
            );
        }

        if (isset($filter['seller_email'])) {
            $conditions[] = sprintf(
                "seller.email = %s",
                $connection->getWrappedConnection()->quote($filter['seller_email'])
            );
        }

        if (isset($filter['offer_id'])) {
            $conditions[] = sprintf(
                "click.offer_id = %s",
                $connection->getWrappedConnection()->quote(
                    Uuid::fromString($filter['offer_id'])->toString()
                )
            );
        }

        if (!empty($conditions)) {
            $condition = 'where ' . implode(' and ', $conditions);
        }

        $sqlQuery = <<<SQL
select
    click.ctime click_time,
    event.ctime event_time,
    parent.email parent_email,
    seller.email seller_email,
    network.email network_name,
    click.status click_status,
    event_type.title event_title,
    event.event_type event_name,
    click.offer_id,
    offer.title offer_name,
    click.id click_id,
    event.amount_for_employee sum_fee,
    event.source event_source

from actiondata.offer_execution click
join offerdata.offer offer on offer.id = click.offer_id
left join actiondata.sdk_event event on event.offer_execution_id = click.id
left join offerdata.event_type event_type on event_type.code = event.event_type
join actiondata.user_offer_link UOL on UOL.id = click.source_link_id
join userdata."user" seller on seller.id = UOL.user_id
join userdata.profile seller_profile on seller_profile.user_id = seller.id
left join userdata."user" network on network.id = seller_profile.employer_id
left join actiondata.followed_user fu on fu.whom_user_id = seller.id
left join userdata."user" parent on parent.id = fu.who_user_id

$condition

order by click.ctime
SQL;

        return $connection
            ->executeQuery($sqlQuery)
            ->fetchAll(FetchMode::ASSOCIATIVE);
    }
}