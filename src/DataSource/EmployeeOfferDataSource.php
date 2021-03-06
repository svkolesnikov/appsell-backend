<?php

namespace App\DataSource;

use App\DataSource\Dto\EmployeeOffer;
use App\DataSource\Dto\StatisticItem;
use App\Entity\BaseCommission;
use App\Entity\User;
use App\Exception\Api\DataSourceException;
use App\Lib\Enum\CommissionEnum;
use App\Lib\Enum\OfferExecutionStatusEnum;
use App\Lib\Enum\OfferTypeEnum;
use App\Service\ImageService;
use App\SolarStaff\Client;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

class EmployeeOfferDataSource
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var Client */
    protected $solarStaffClient;

    /** @var ImageService */
    protected $imageService;

    public function __construct(EntityManagerInterface $em, Client $ssc, ImageService $imageService)
    {
        $this->entityManager = $em;
        $this->solarStaffClient = $ssc;
        $this->imageService = $imageService;
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
        // Идентификатор компании-продавца, которая соответствует SS
        // Для сотрудников этой компании видны все офферы, которые активны.
        // В отличии от остальных сотрудников, для которых их компания продавец
        // должна одобрить отображение оффера
        $solarStaffEmployerId = $this->solarStaffClient->getEmployerId();

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
    select distinct
      O.*
    from offerdata.offer O
    left join offerdata.seller_approved_offer AO on AO.offer_id = O.id
    where
      O.is_active = true AND
      O.active_from < now() AND
      O.active_to > now() and
      O.type in ('$types') and
      AO.seller_id = (select id from employer limit 1) -- Условие для простых сотрудников компаний
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
        else C.price
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
      round(P.price) price,
      P.currency,
      P.type,
      P.description
    from employee_prices P
    where P.offer_id = O.id
    order by P.type desc
  ) as r) as compensations,
  
  (select json_agg(r) from (
    select
      L.type,
      L.image
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

            $availableOffers = array_map(function (array $item) {

                $item['image'] = null;
                $item['compensations'] = (array) json_decode($item['compensations'], true);
                $item['links'] = (array) json_decode($item['links'], true);

                foreach ($item['links'] as $link) {

                    if (empty($link['image'])) {
                        continue;
                    }

                    $item['image'] = $this->imageService->getPublicUrl($link['image']);

                    break;
                }

                return new EmployeeOffer($item);

            }, $statement->fetchAll(FetchMode::ASSOCIATIVE));

            // Если нужно удерживать комиссию за вывод средств
            // то ее нужно учесть при показе доступных компенсацию пользователю

            $employeer = $employee->getProfile()->getEmployer();
            $hasPayoutCommission = null !== $employeer
                && $employeer->getProfile()->isCompanyPayoutOverSolarStaff()
                && $employee->getProfile()->isSolarStaffConnected();

            if ($hasPayoutCommission) {

                /** @var BaseCommission $payoutBaseCommission */
                $payoutBaseCommission = $this->entityManager
                    ->getRepository('App:BaseCommission')
                    ->findOneBy(['type' => CommissionEnum::SOLAR_STAFF_PAYOUT]);

                if (null !== $payoutBaseCommission && $payoutPercent = $payoutBaseCommission->getPercent()) {

                    /** @var EmployeeOffer $offer */
                    foreach ($availableOffers as $offer) {
                        foreach ($offer->compensations as $compensation) {
                            $compensation->price = (int) $compensation->price - round((int) $compensation->price * $payoutPercent / 100, 2);
                        }
                    }
                }
            }

            return $availableOffers;

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
WITH source_data AS (
    SELECT
      o.id AS offer_id,
      o.title AS offer_title,
      COALESCE(se.amount_for_employee, 0) AS price,
      oe.id AS execution_id,
      oe.status,
      ( 
        SELECT image 
        FROM offerdata.offer_link 
        WHERE offer_id = oe.offer_id AND image IS NOT NULL
        ORDER BY type 
        LIMIT 1
      ) AS image
    FROM actiondata.user_offer_link ol
    INNER JOIN offerdata.offer o ON o.id = ol.offer_id
    INNER JOIN actiondata.offer_execution oe ON oe.offer_id = ol.offer_id AND oe.source_link_id = ol.id
    LEFT JOIN actiondata.sdk_event se ON se.offer_execution_id = oe.id AND se.ctime BETWEEN o.active_from AND o.active_to
    WHERE ol.user_id = :employee_id AND oe.status = :status
), distinct_data AS (
    SELECT offer_id AS id, offer_title AS title, execution_id, null AS reason, SUM(price) AS sum_price, image
    FROM source_data
    GROUP BY offer_id , offer_title, execution_id, image
)

SELECT id, title, image, null AS reason, COUNT(id), round(SUM(sum_price)) AS sum
FROM distinct_data
GROUP BY id, title, image, reason
SQL;

        try {

            $connection = $this->entityManager->getConnection();
            $statement = $connection->prepare($sql);
            $statement->bindValue('employee_id', $employee->getId(), ParameterType::STRING);
            $statement->bindValue('status', $status->getValue(), ParameterType::STRING);
            $statement->execute();

            return array_map(function (array $item) {

                if (!empty($item['image'])) {
                    $item['image'] = $this->imageService->getPublicUrl($item['image']);
                }

                return new StatisticItem($item);

            }, $statement->fetchAll(FetchMode::ASSOCIATIVE));

        } catch (DBALException $ex) {
            throw new DataSourceException($ex->getMessage(), $ex);
        }
    }
}