<?php

namespace App\Queue\MessageHandler;

use App\DCI\PushCreating;
use App\Entity\PushNotificationLog;
use App\Lib\Enum\PushNotificationStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

class PushDirectHandler implements HandlerInterface
{
    /** @var  EntityManagerInterface */
    protected $em;

    /** @var  LoggerInterface */
    protected $logger;

    /** @var  PushCreating */
    protected $pushCreating;

    public function __construct(EntityManagerInterface $em, LoggerInterface $l, PushCreating $p)
    {
        $this->em = $em;
        $this->logger = $l;
        $this->pushCreating = $p;
    }

    public function handle(array $message): string
    {
        $logId = $message['log'];

        /** @var PushNotificationLog $log */
        $log = $this->em->getRepository(PushNotificationLog::class)->find($logId);
        if (null === $log) {

            $this->logger->error('Не обнаружен лог уведомления ', [
                'error' => 'Не обнаружен лог уведомление',
                'log'   => $logId
            ]);

            return PsrProcessor::REJECT;
        }

        try {

            $data = $this->pushCreating->send($message['message'], json_decode($message['data'], true), $log->getDevice()->getToken());

            $log->setMulticastId($data['multicast_id']);

            if (1 === $data['success']) {
                $log->setInfo($data['results'][0]['message_id']);
                $log->setStatus(PushNotificationStatusEnum::SUCCESS());
            } else {

                $log->setError(substr($data['results'][0]['error'], 0, 255));
                $log->setStatus(PushNotificationStatusEnum::ERROR());
            }

        } catch (\Exception $ex) {
            $this->logger->error('Не удалось отправить push для указанного устройства: ' . $ex->getMessage());

            $log->setError(substr('Не удалось отправить push для указанного устройства: ' . $ex->getMessage(), 0, 255));
            $log->setStatus(PushNotificationStatusEnum::ERROR());
        }

        $this->em->persist($log);
        $this->em->flush();

        return PsrProcessor::ACK;
    }
}