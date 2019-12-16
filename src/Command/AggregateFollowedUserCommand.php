<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AggregateFollowedUserCommand extends Command
{
    use LockableTrait;

    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /** @var  EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:followed-user:aggregate')
            ->setDescription(
                'Формирование данных для таблицы actiondata.followed_user на основе событий от SDK'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Обезопасим себя от нескольких процессов за раз
        if (!$this->lock()) {
            return 0;
        }

        $connection = $this->em->getConnection();

        // Получим последнюю метку времени, с которой
        // нужно начать смотреть есть ли изменения

        $checkPointSql = <<<SQL
select coalesce(max(ctime), '2019-06-10 00:00:00'::timestamp) "date" 
from actiondata.followed_user
SQL;

        $lastCheckPoint = \DateTime::createFromFormat(
            self::DATE_FORMAT,
            $connection->executeQuery($checkPointSql)->fetchAll()[0]['date']
        );

        // Начнем получать события

        $eventsSql = <<<SQL
select
    L.user_id who_user_id,
    U.id whom_user_id,
    E.amount_for_employee earned_amount,
    E.ctime
from actiondata.sdk_event E
join actiondata.offer_execution EX on EX.id = E.offer_execution_id
join actiondata.user_offer_link L on L.id = EX.source_link_id
join userdata."user" U on U.email = trim('"' from (E.source_info -> 'event_data' -> 'email')::varchar)
where
    E.ctime > :ctime
    and EX.status = 'complete'
order by E.ctime
limit :limit
SQL;

        $insertSql = <<<SQL
insert into actiondata.followed_user (who_user_id, whom_user_id, earned_amount, ctime)
values (:who, :whom, :earned, :ctime); 
SQL;


        $nextCheckpoint = $lastCheckPoint;
        $limit          = 100;

        do {

            $continue   = false;
            $eventsStmt = $connection->executeQuery($eventsSql, [
                'limit' => $limit,
                'ctime' => $nextCheckpoint->format(self::DATE_FORMAT)
            ]);

            while (false !== ($event = $eventsStmt->fetch())) {

                $connection->executeQuery($insertSql, [
                    'who'       => $event['who_user_id'],
                    'whom'      => $event['whom_user_id'],
                    'earned'    => $event['earned_amount'],
                    'ctime'     => $event['ctime'],
                ])->closeCursor();

                $nextCheckpoint = \DateTime::createFromFormat(self::DATE_FORMAT, $event['ctime']);
                $continue = true;
            }

            $eventsStmt->closeCursor();
        } while ($continue);

        return 0;
    }
}