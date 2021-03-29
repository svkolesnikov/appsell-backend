<?php

namespace App\DataSource;

use App\DataSource\Dto\ReportItem;
use App\DataSource\Dto\StatisticItem;
use App\DataSource\Dto\SellerOffer;
use App\Entity\PromoCode;
use App\Entity\User;
use App\Exception\Api\DataSourceException;
use App\Lib\Enum\OfferExecutionStatusEnum;
use App\Lib\Enum\OfferTypeEnum;
use App\Service\ImageService;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

class SellerOfferDataSource
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var ImageService */
    protected $imageService;

    public function __construct(EntityManagerInterface $em, ImageService $imageService)
    {
        $this->entityManager = $em;
        $this->imageService = $imageService;
    }

    /**
     * @param User $seller
     * @param int $limit
     * @param int $offset
     * @param OfferTypeEnum|null $type
     * @return array|SellerOffer[]
     * @throws DataSourceException
     */
    public function getAvailableOffers(User $seller, int $limit, int $offset, OfferTypeEnum $type = null): array
    {
        $types = null === $type
            ? implode("', '", OfferTypeEnum::toArray())
            : $type->getValue();

        $sql = <<<SQL
with base_commission as (
    select * from financedata.base_commission where type = 'service' limit 1
  ),
  person_commission as (
    select * from financedata.for_user_commission where user_id = :seller_id and by_user_id is null limit 1
  ),
  offers as (
    select O.* from offerdata.offer O
    where
      O.is_active = true AND
      O.active_from < now() AND
      O.active_to > now() and
      O.type in ('$types')
  ),
  compensations as (
    select
      C.*,
      OC.percent as offer_commission,
      (select percent from person_commission) as user_commission,
      (select percent from base_commission) as base_commission
    from offers O
    join offerdata.compensation C on C.offer_id = O.id
    left join financedata.for_offer_commission OC on OC.offer_id = O.id and OC.by_user_id is null
  ),
  app_links as (
    select * from offerdata.offer_link where offer_id in (
      select id from offers
    )
  ),
  prices as (
    select
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
    from compensations C
  )
select
  O.*,
  
  (select json_agg(r) from (
    select
      round(P.price) price,
      P.currency,
      P.type,
      P.description
    from prices P
    where P.offer_id = O.id
    order by P.type desc
  ) as r) as compensations,
  
  (select count(*) from (
    select
      *
    from promo_codes pc
    where pc.offer_id = O.id AND pc.status = :promo_code_status AND pc.user_id is null
  ) as p) as promo_codes,
  
  (select json_agg(r) from (
    select
      L.type,
      L.image
    from app_links L
    where L.offer_id = O.id
    order by L.type
  ) as r) as links,
  
  (AO.id is not null) as is_approved
  
from offers O
left join offerdata.seller_approved_offer AO on AO.offer_id = O.id and AO.seller_id = :seller_id
order by O.mtime desc
limit :limit 
offset :offset
SQL;

        try {

            $connection = $this->entityManager->getConnection();
            $statement = $connection->prepare($sql);
            $statement->bindValue('seller_id', $seller->getId(), ParameterType::STRING);
            $statement->bindValue('limit', $limit, ParameterType::INTEGER);
            $statement->bindValue('offset', $offset, ParameterType::INTEGER);
            $statement->bindValue('promo_code_status', PromoCode::STATUS_FRESH, ParameterType::STRING);
            $statement->execute();

            return array_map(function (array $item) {

                $item['image'] = null;
                $item['compensations'] = (array) json_decode($item['compensations'], true);

                $item['promo_codes'] = !empty($item['promo_codes']);
                $item['links'] = (array) json_decode($item['links'], true);

                foreach ($item['links'] as $link) {

                    if (empty($link['image'])) {
                        continue;
                    }

                    $item['image'] = $this->imageService->getPublicUrl($link['image']);

                    break;
                }

                return new SellerOffer($item);

            }, $statement->fetchAll(FetchMode::ASSOCIATIVE));

        } catch (DBALException $ex) {
            throw new DataSourceException($ex->getMessage(), $ex);
        }
    }

    /**
     * Получение статистики исполнению офферов за все время
     *
     * @param User $seller
     * @param OfferExecutionStatusEnum $status
     * @return array
     * @throws DataSourceException
     */
    public function getExecutionStatistic(User $seller, OfferExecutionStatusEnum $status): array
    {
        $sql = <<<SQL
WITH source_data AS (
    SELECT
      p.user_id,
      CONCAT(u.email, ' (', p.lastname, ' ', p.firstname,')') AS fullname,
      COALESCE(se.amount_for_seller, 0) AS price,
      oe.id AS execution_id,
      oe.status,
      o.id as offer_id
    FROM userdata.profile p
    INNER JOIN userdata.user u ON u.id = p.user_id
    INNER JOIN actiondata.user_offer_link ol ON ol.user_id = p.user_id
    INNER JOIN actiondata.offer_execution oe ON oe.offer_id = ol.offer_id AND oe.source_link_id = ol.id
    INNER JOIN offerdata.offer o ON o.id = ol.offer_id
    LEFT JOIN actiondata.sdk_event se ON se.offer_execution_id = oe.id AND se.ctime BETWEEN o.active_from AND o.active_to
    WHERE p.employer_id = :employer_id AND oe.status = :status
), distinct_data AS (
    SELECT user_id AS id, fullname AS title, execution_id, null AS reason, SUM(price) AS sum_price, offer_id
    FROM source_data
    GROUP BY user_id , fullname, execution_id, offer_id
)

SELECT 
    id, 
    title,
    ( 
        SELECT image 
        FROM offerdata.offer_link 
        WHERE offer_id = offer_id AND image IS NOT NULL
        ORDER BY type 
        LIMIT 1
    ) AS image,
   null AS reason, 
   COUNT(id), 
   ROUND(SUM(sum_price)) AS sum
FROM distinct_data
GROUP BY id, title, reason
SQL;

        try {

            $connection = $this->entityManager->getConnection();
            $statement = $connection->prepare($sql);
            $statement->bindValue('employer_id', $seller->getId(), ParameterType::STRING);
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

    /**
     * Получение финансового отчета по исполнению за определенный промежуток времени
     *
     * @param User $seller
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     * @throws DataSourceException
     */
    public function getFinanceReport(User $seller, \DateTime $startDate, \DateTime $endDate): array
    {
        $sql = <<<SQL
        
WITH source_data AS (
    SELECT
      o.id,
      o.title,
      COALESCE(se.amount_for_seller, 0) AS price,
      oe.id AS execution_id
    FROM userdata.profile p
    INNER JOIN actiondata.user_offer_link ol ON ol.user_id = p.user_id
    INNER JOIN actiondata.offer_execution oe ON oe.offer_id = ol.offer_id AND oe.source_link_id = ol.id
    INNER JOIN offerdata.offer o ON o.id = ol.offer_id
    INNER JOIN actiondata.sdk_event se ON se.offer_execution_id = oe.id AND se.ctime BETWEEN o.active_from AND o.active_to
    WHERE p.employer_id = :employer_id 
        AND oe.status IN ('processing', 'complete') 
        AND se.ctime BETWEEN :start_date AND :end_date
), distinct_data AS (
    SELECT id, title, execution_id, round(SUM(price), 2) AS sum_price, round((SUM(price) * 18 / 100), 2) AS sum_tax
    FROM source_data
    GROUP BY id, title
)

SELECT id, title, COUNT(*), SUM(sum_price) AS sum, SUM(sum_tax) AS tax
FROM distinct_data
GROUP BY id, title
SQL;

        try {

            $connection = $this->entityManager->getConnection();
            $statement = $connection->prepare($sql);
            $statement->bindValue('employer_id', $seller->getId(), ParameterType::STRING);
            $statement->bindValue('start_date', $startDate->format('Y-m-d H:i:s'), ParameterType::STRING);
            $statement->bindValue('end_date', $endDate->format('Y-m-d H:i:s'), ParameterType::STRING);
            $statement->execute();

            return array_map(function (array $item) {
                return new ReportItem($item);
            }, $statement->fetchAll(FetchMode::ASSOCIATIVE));

        } catch (DBALException $ex) {
            throw new DataSourceException($ex->getMessage(), $ex);
        }
    }
}