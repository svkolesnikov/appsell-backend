<?php

namespace App\Queue\MessageHandler;

use App\DCI\PushCreating;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Queue\Processor;
use Psr\Log\LoggerInterface;

class PushDirectHandler implements HandlerInterface
{
    /** @var  EntityManagerInterface */
    protected $em;

    /** @var  LoggerInterface */
    protected $logger;

    /** @var  PushCreating */
    protected $pushCreating;

    public function __construct(EntityManagerInterface $em, $pushNotificationLogger, PushCreating $p)
    {
        $this->em = $em;
        $this->logger = $pushNotificationLogger;
        $this->pushCreating = $p;
    }

    public function handle(array $message): string
    {
        $logParams = $message;
        unset($logParams['message']);

        try {

            $data = $this->pushCreating->send(
                $message['message'], json_decode($message['data'], true), $message['device_token']
            );

            if (1 === $data['success']) {
                $this->logger->info('Push уведомление успешно отправлено', [
                    'source_message' => $logParams,
                    'response_data'  => $data
                ]);

            } else {
                $this->logger->error('Не удалось отправить push: ' . $data['results'][0]['error'], [
                    'source_message' => $logParams,
                    'response_data'  => $data
                ]);
            }

        } catch (\Exception $ex) {
            $this->logger->error('Не удалось отправить push: ' . $ex->getMessage(), ['source_message' => $message]);
        }

        return Processor::ACK;
    }
}