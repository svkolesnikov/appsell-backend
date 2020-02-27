<?php

namespace App\Queue\MessageHandler;

use App\Entity\User;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Queue\Processor;
use Psr\Log\LoggerInterface;

class ReportHandler implements HandlerInterface
{
    /** @var  EntityManagerInterface */
    protected $em;

    /** @var  ReportService */
    protected $reportService;

    /** @var  LoggerInterface */
    protected $logger;

    public function __construct(ReportService $rs, EntityManagerInterface $em, LoggerInterface $l)
    {
        $this->em = $em;
        $this->reportService = $rs;
        $this->logger = $l;
    }

    public function handle(array $message): string
    {
        $userId     = $message['user_id'];
        $startDate  = new \DateTime($message['start_date']);
        $endDate    = new \DateTime($message['end_date']);

        /** @var User $user */
        $user       = $this->em->getRepository(User::class)->find($userId);

        if (null === $user) {

            $this->logger->error('Не удалось сформировать отчет', [
                'error' => 'Пользователь не обнаружен',
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return Processor::REJECT;
        }

        try {

            $this->reportService->create($user, $startDate, $endDate);

        } catch (\Exception $ex) {
            $this->logger->error('Ошибка при формировании отчета', [
                'error' => $ex->getMessage(),
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return Processor::REQUEUE;
        }

        return Processor::ACK;
    }
}