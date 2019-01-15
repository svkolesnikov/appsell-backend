<?php

namespace App\DataSource;

use App\DataSource\Dto\ReportItem;
use App\DataSource\Dto\StatisticItem;
use App\Entity\User;
use App\Exception\Api\DataSourceException;
use App\Lib\Enum\OfferExecutionStatusEnum;
use App\Service\ImageService;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

class OwnerOfferDataSource
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
     * Получение статистики исполнению офферов за все время
     *
     * @param User $owner
     * @param OfferExecutionStatusEnum $status
     * @return array
     * @throws DataSourceException
     */
    public function getExecutionStatistic(User $owner, OfferExecutionStatusEnum $status): array
    {
        $sql = <<<SQL
WITH source_data AS (
    SELECT
      o.id,
        o.title,
        COALESCE(c.price, 0) as price,
        oe.status,
        oe.id AS execution_id,
        ( 
          SELECT image 
          FROM offerdata.offer_link 
          WHERE offer_id = oe.offer_id AND image IS NOT NULL
          ORDER BY type 
          LIMIT 1
        ) AS image
    FROM offerdata.offer o
    LEFT JOIN actiondata.user_offer_link ol ON o.id = ol.offer_id
    LEFT JOIN actiondata.offer_execution oe ON oe.offer_id = ol.offer_id
    LEFT JOIN actiondata.sdk_event se ON se.offer_execution_id = oe.id AND se.ctime BETWEEN o.active_from AND o.active_to
    INNER JOIN offerdata.compensation c ON c.offer_id = o.id AND c.event_type = se.event_type
    WHERE o.owner_id = :owner_id AND oe.status = :status
), distinct_data AS (
    SELECT id, title, execution_id, null AS reason, SUM(price) AS sum_price, image
    FROM source_data
    GROUP BY id, title, execution_id, image
)

SELECT id, title, image, null AS reason, COUNT(id), ROUND(SUM(sum_price)) AS sum
FROM distinct_data
GROUP BY id, title, reason, image
SQL;

        try {

            $connection = $this->entityManager->getConnection();
            $statement = $connection->prepare($sql);
            $statement->bindValue('owner_id', $owner->getId(), ParameterType::STRING);
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
     * @param User $owner
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     * @throws DataSourceException
     */
    public function getFinanceReport(User $owner, \DateTime $startDate, \DateTime $endDate): array
    {
        $sql = <<<SQL
WITH source_data AS (
    SELECT
      o.id,
      o.title,
      COALESCE(c.price, 0) as price,
      oe.id AS execution_id
    FROM offerdata.offer o
    LEFT JOIN actiondata.user_offer_link ol ON o.id = ol.offer_id
    LEFT JOIN actiondata.offer_execution oe ON oe.offer_id = ol.offer_id
    LEFT JOIN actiondata.sdk_event se ON se.offer_execution_id = oe.id AND se.ctime BETWEEN o.active_from AND o.active_to
    INNER JOIN offerdata.compensation c ON c.offer_id = o.id AND c.event_type = se.event_type
    WHERE o.owner_id = :owner_id 
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
            $statement->bindValue('owner_id', $owner->getId(), ParameterType::STRING);
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