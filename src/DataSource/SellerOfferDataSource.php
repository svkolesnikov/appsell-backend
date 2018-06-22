<?php

namespace App\DataSource;

use App\DataSource\Dto\SellerOffer;
use App\Entity\User;
use App\Exception\Api\DataSourceException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

class SellerOfferDataSource
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * @param User $seller
     * @param int $limit
     * @param int $offset
     * @return array|SellerOffer[]
     * @throws DataSourceException
     */
    public function getAvailableOffers(User $seller, int $limit, int $offset): array
    {
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
      O.active_to > now()
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
      end as price
    from compensations C
  )
select
  O.*,
  (select json_agg(r) from (
    select
      P.price,
      P.currency,
      P.type,
      P.description
    from prices P
    where P.offer_id = O.id
  ) as r) as compensations
from offers O
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
            $statement->execute();

            return array_map(function (array $item) {

                $item['compensations'] = (array) json_decode($item['compensations'], true);
                return new SellerOffer($item);

            }, $statement->fetchAll(FetchMode::ASSOCIATIVE));

        } catch (DBALException $ex) {
            throw new DataSourceException($ex->getMessage(), $ex);
        }
    }
}