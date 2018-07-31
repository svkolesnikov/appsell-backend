<?php

namespace App\Command;

use App\Entity\User;
use App\Lib\Enum\UserGroupEnum;
use App\Queue\Processor\Processor;
use App\Queue\Producer\Producer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateReportDeferredCommand extends Command
{
    /** @var  EntityManagerInterface */
    protected $em;

    /** @var Producer */
    protected $producer;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(EntityManagerInterface $em, Producer $p, LoggerInterface $logger)
    {
        parent::__construct();

        $this->em = $em;
        $this->producer = $p;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('app:report:create-deferred')
            ->setDescription('Создание отчетов через очередь за текущий месяц (если не указан иной период)')
            ->addArgument('start_date', InputArgument::OPTIONAL, 'Начало периода (yyyy-mm-dd)', date('Y-m-01 00:00:00'))
            ->addArgument('end_date',   InputArgument::OPTIONAL, 'Конец периода (yyyy-mm-dd)', date('Y-m-t 23:59:59'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startDate  = new \DateTime($input->getArgument('start_date'));
        $endDate    = new \DateTime($input->getArgument('end_date'));

        $output->writeln('Постановка задач на формирование отчетов');

        $qb = $this->em->createQueryBuilder();

        /** @var User[] $users */
        $users = $qb
            ->select('u')
            ->from(User::class, 'u')
            ->innerJoin('u.groups', 'g')
            ->where($qb->expr()->in('g.code', ':code'))
            ->setParameter('code', [UserGroupEnum::OWNER, UserGroupEnum::SELLER])
            ->getQuery()
            ->execute();

        foreach ($users as $user) {

            $message = [
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'end_date'   => $endDate->format('Y-m-d H:i:s'),
                'user_id'    => $user->getId()
            ];

            try {

                $this->producer->send(Processor::QUEUE_REPORT, $message);

            } catch (\Exception $ex) {
                $this->logger->error(
                    'Ошибка при постановке сообщения в очередь',
                    array_merge($message, ['error' => $ex->getMessage()])
                );
            }
        }

        $output->writeln('Формирование задач завершено!');
    }
}