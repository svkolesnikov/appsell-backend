<?php

namespace App\DataSource;

use App\DataSource\Dto\EmployeeOffer;
use App\DataSource\Dto\StatisticItem;
use App\Entity\User;
use App\Exception\Api\DataSourceException;
use App\Lib\Enum\OfferExecutionStatusEnum;
use App\Lib\Enum\OfferTypeEnum;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

class EmployeeOfferDataSource
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * @param User $employee
     * @param int $limit
     * @param int $offset
     * @param OfferTypeEnum|null $type
     * @return array|EmployeeOffer[]
     * @throws DataSourceException
     */
    public function getAvailableOffers(User $employee, int $limit, int $offset, OfferTypeEnum $type = null): array
    {
        $types = null === $type
            ? implode("', '", OfferTypeEnum::toArray())
            : $type->getValue();

        $sql = <<<SQL
with employer as (
    select * from userdata.user where id = (
      select employer_id from userdata.profile where user_id = :employee_id
    )
  ),

  -- Рассчет комиссий, которые забираются сервисом

  base_service_comm as (
    select * from financedata.base_commission where type = 'service' limit 1
  ),
  seller_pers_commission as (
    select * from financedata.for_user_commission
    where
      user_id = (select id from employer limit 1)
      and by_user_id is null limit 1
  ),
  offers as (
    select
      O.*
    from offerdata.offer O
    join offerdata.seller_approved_offer AO on AO.offer_id = O.id
    where
      O.is_active = true AND
      O.active_from < now() AND
      O.active_to > now() and
      O.type in ('$types') and
      AO.seller_id = (select id from employer limit 1)
  ),
  app_links as (
    select * from offerdata.offer_link where offer_id in (
      select id from offers
    )
  ),
  seller_compensations as (
    select
      C.*,
      OC.percent as offer_commission,
      (select percent from seller_pers_commission) as user_commission,
      (select percent from base_service_comm) as base_commission
    from offers O
    join offerdata.compensation C on C.offer_id = O.id
    left join financedata.for_offer_commission OC on OC.offer_id = O.id and OC.by_user_id is null
  ),
  seller_prices as (
    select
      C.id,
      C.type,
      C.description,
      C.currency,
      C.offer_id,
      case
        when C.offer_commission is not null and C.offer_commission > 0 then C.price - C.price * C.offer_commission * 0.01
        when C.user_commission is not null and C.user_commission > 0 then C.price - C.price * C.user_commission * 0.01
        when C.base_commission is not null and C.base_commission > 0 then C.price - C.price * C.base_commission * 0.01
      end as price
    from seller_compensations C
  ),

  -- Рассчет комиссий, которые забираются продавцом с сотрудников

  base_seller_comm as (
    select * from financedata.base_commission where type = 'seller' limit 1
  ),
  base_seller_pers_comm as (
    select * from financedata.seller_base_commission where seller_id = (
      select id from employer limit 1
    )
  ),
  seller_offers_comm as (
    select * from financedata.for_offer_commission
    where
      by_user_id = (select id from employer limit 1) and
      offer_id in (select id from offers)
  ),
  employee_compensations as (
    select
      SP.id,
      SP.type,
      SP.description,
      SP.currency,
      SP.offer_id,
      SP.price,
      (select percent from base_seller_comm) as base_commission,
      (select percent from base_seller_pers_comm) as seller_commission,
      (select percent from seller_offers_comm where offer_id = SP.offer_id) as offer_commission
    from seller_prices SP
  ),
  employee_prices as (
    select
      C.id,
      C.type,
      C.description,
      C.currency,
      C.offer_id,
      case
        when C.offer_commission is not null and C.offer_commission > 0 then C.price - C.price * C.offer_commission * 0.01
        when C.seller_commission is not null and C.seller_commission > 0 then C.price - C.price * C.seller_commission * 0.01
        when C.base_commission is not null and C.base_commission > 0 then C.price - C.price * C.base_commission * 0.01
        else C.price
      end as price
    from employee_compensations C
  )

select
  O.*,
  
  (select json_agg(r) from (
    select
      P.price,
      P.currency,
      P.type,
      P.description
    from employee_prices P
    where P.offer_id = O.id
    order by P.type desc
  ) as r) as compensations,
  
  (select json_agg(r) from (
    select
      L.type
    from app_links L
    where L.offer_id = O.id
    order by L.type
  ) as r) as links
  
from offers O
order by mtime desc
limit :limit 
offset :offset
SQL;

        try {

            $connection = $this->entityManager->getConnection();
            $statement = $connection->prepare($sql);
            $statement->bindValue('employee_id', $employee->getId(), ParameterType::STRING);
            $statement->bindValue('limit', $limit, ParameterType::INTEGER);
            $statement->bindValue('offset', $offset, ParameterType::INTEGER);
            $statement->execute();

            return array_map(function (array $item) {

                $item['compensations'] = (array) json_decode($item['compensations'], true);
                $item['links'] = (array) json_decode($item['links'], true);

                return new EmployeeOffer($item);

            }, $statement->fetchAll(FetchMode::ASSOCIATIVE));

        } catch (DBALException $ex) {
            throw new DataSourceException($ex->getMessage(), $ex);
        }
    }

    /**
     * @param User $employee
     * @param OfferExecutionStatusEnum $status
     * @return array
     * @throws DataSourceException
     */
    public function getExecutionStatistic(User $employee, OfferExecutionStatusEnum $status): array
    {
        $sql = <<<SQL
WITH data as (
    SELECT
      o.id as offer_id, 
      o.title as offer_title, 
      COALESCE(se.amount_for_employee, 0) as price, 
      oe.status
    FROM actiondata.user_offer_link ol
    INNER JOIN offerdata.offer o ON o.id = ol.offer_id
    INNER JOIN actiondata.offer_execution oe ON oe.offer_id = ol.offer_id AND oe.source_link_id = ol.id
    LEFT JOIN actiondata.sdk_event se ON se.offer_execution_id = oe.id AND se.ctime BETWEEN o.active_from AND o.active_to
    WHERE ol.user_id = :employee_id AND oe.status = :status
)

SELECT offer_id as id, offer_title as title, null as reason, COUNT(*), SUM(price)
FROM data
GROUP BY offer_id , offer_title, reason;
SQL;

        try {

            $connection = $this->entityManager->getConnection();
            $statement = $connection->prepare($sql);
            $statement->bindValue('employee_id', $employee->getId(), ParameterType::STRING);
            $statement->bindValue('status', $status->getValue(), ParameterType::STRING);
            $statement->execute();

            return array_map(function (array $item) {
                return new StatisticItem($item);
            }, $statement->fetchAll(FetchMode::ASSOCIATIVE));

        } catch (DBALException $ex) {
            throw new DataSourceException($ex->getMessage(), $ex);
        }
    }
}