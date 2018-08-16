<?php

namespace App\DataSource;

use App\DataSource\Dto\ReportItem;
use App\DataSource\Dto\StatisticItem;
use App\Entity\User;
use App\Exception\Api\DataSourceException;
use App\Lib\Enum\OfferExecutionStatusEnum;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

class OwnerOfferDataSource
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
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
WITH data as (
    SELECT DISTINCT
        se.id as event_id,
        o.id,
        o.title,
        COALESCE(c.price, 0) as price,
        oe.status
    FROM offerdata.offer o
    LEFT JOIN actiondata.user_offer_link ol ON o.id = ol.offer_id
    LEFT JOIN actiondata.offer_execution oe ON oe.offer_id = ol.offer_id
    LEFT JOIN actiondata.sdk_event se ON se.offer_execution_id = oe.id AND se.ctime BETWEEN o.active_from AND o.active_to
    INNER JOIN offerdata.compensation c ON c.offer_id = o.id AND c.event_type = se.event_type
    WHERE o.owner_id = :owner_id AND oe.status = :status
)

SELECT id, title, null as reason, COUNT(*), SUM(price)
FROM data
GROUP BY id, title, reason;
SQL;

        try {

            $connection = $this->entityManager->getConnection();
            $statement = $connection->prepare($sql);
            $statement->bindValue('owner_id', $owner->getId(), ParameterType::STRING);
            $statement->bindValue('status', $status->getValue(), ParameterType::STRING);
            $statement->execute();

            return array_map(function (array $item) {
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
WITH data as (
    SELECT DISTINCT
        se.id as event_id,
        o.id,
        o.title,
        COALESCE(c.price, 0) as price
    FROM offerdata.offer o
    LEFT JOIN actiondata.user_offer_link ol ON o.id = ol.offer_id
    LEFT JOIN actiondata.offer_execution oe ON oe.offer_id = ol.offer_id
    LEFT JOIN actiondata.sdk_event se ON se.offer_execution_id = oe.id AND se.ctime BETWEEN o.active_from AND o.active_to
    INNER JOIN offerdata.compensation c ON c.offer_id = o.id AND c.event_type = se.event_type
    WHERE o.owner_id = :owner_id AND oe.status IN ('processing', 'complete') AND se.ctime BETWEEN :start_date AND :end_date
)

SELECT id, title, COUNT(*), SUM(price), (SUM(price) * 18 / 100) as tax
FROM data
GROUP BY id, title;
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