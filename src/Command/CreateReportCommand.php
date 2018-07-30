<?php

namespace App\Command;

use App\Entity\User;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateReportCommand extends Command
{
    /** @var  EntityManagerInterface */
    protected $em;

    /** @var ReportService */
    protected $reportService;

    public function __construct(ReportService $rs, EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
        $this->reportService = $rs;
    }

    protected function configure()
    {
        $this
            ->setName('app:report:create')
            ->setDescription('Создание отчета по указанному пользователю за указанный период (по умолчанию текущий месяц)')
            ->addArgument('user_id',    InputArgument::REQUIRED, 'Идентификатор пользователя')
            ->addArgument('start_date', InputArgument::OPTIONAL, 'Начало периода (ГГГГ-ММ-ДД ЧЧ:ММ:СС)', date('Y-m-01 00:00:00'))
            ->addArgument('end_date',   InputArgument::OPTIONAL, 'Конец периода (ГГГГ-ММ-ДД ЧЧ:ММ:СС)', date('Y-m-t 23:59:59'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userId     = $input->getArgument('user_id');
        $startDate  = new \DateTime($input->getArgument('start_date'));
        $endDate    = new \DateTime($input->getArgument('end_date'));

        /** @var User $user */
        $user       = $this->em->getRepository(User::class)->find($userId);

        if (null === $user) {
            $output->writeln(sprintf('Пользователь %s не обнаружен', $userId));
            return;
        }

        try {

            $this->reportService->create($user, $startDate, $endDate);

        } catch (\Exception $ex) {
            $output->writeln(sprintf('При формировании отчета произошла ошибка: %s', $ex->getMessage()));
            return;
        }

        $output->writeln('Формирование отчетов завершено!');
    }
}