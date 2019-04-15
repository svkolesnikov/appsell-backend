<?php

namespace App\Queue\MessageHandler;

use App\Entity\PushNotification;
use App\Lib\Enum\PushNotificationStatusEnum;
use App\Queue\Processor\Processor;
use App\Queue\Producer\Producer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

class PushCommonHandler implements HandlerInterface
{
    /** @var  EntityManagerInterface */
    protected $em;

    /** @var  LoggerInterface */
    protected $logger;

    /** @var  Producer */
    protected $producer;

    public function __construct(EntityManagerInterface $em, $pushNotificationLogger, Producer $p)
    {
        $this->em = $em;
        $this->logger = $pushNotificationLogger;
        $this->producer = $p;
    }

    public function handle(array $message): string
    {
        $notificationId = $message['notification'];

        /** @var PushNotification $notification */
        $notification   = $this->em->getRepository(PushNotification::class)->find($notificationId);

        if (null === $notification) {

            $this->logger->error('Не обнаружено уведомление для отправки push\'а', [
                'error' => 'Не обнаружено уведомление',
                'notification' => $notificationId
            ]);

            return PsrProcessor::REJECT;
        }

        $notification->setStatus(PushNotificationStatusEnum::IN_PROCESS());

        $this->em->persist($notification);
        $this->em->flush();

        try {

            $devices    = [];
            $recipients = json_decode($notification->getRecipients(), true);

            // в получателях могут быть как отдельные пользователи
            if (array_key_exists('users', $recipients)) {
                $recipientsIds = $recipients['users'];

                $sql = <<<SQL
SELECT DISTINCT user_id, token
FROM userdata.device_push_token
WHERE user_id IN (:user_ids)
SQL;

                $rsm = new ResultSetMapping();
                $rsm
                    ->addScalarResult('user_id', 'user_id')
                    ->addScalarResult('token', 'token');

                $query = $this->em->createNativeQuery($sql, $rsm);
                $query->setParameter('user_ids', $recipientsIds);

                $devices = $query->execute();
            }

            // так и целые группы
            if (array_key_exists('groups', $recipients)) {
                $sql = <<<SQL
SELECT DISTINCT t.user_id, t.token
FROM userdata.device_push_token t
INNER JOIN userdata.user u ON u.id = t.user_id
INNER JOIN userdata.user2group u2g ON u.id = u2g.user_id
INNER JOIN userdata.group g ON g.id = u2g.group_id
WHERE g.code IN (:groups)
SQL;

                $rsm = new ResultSetMapping();
                $rsm
                    ->addScalarResult('user_id', 'user_id')
                    ->addScalarResult('token', 'token');

                $query = $this->em->createNativeQuery($sql, $rsm);
                $query->setParameter('groups', $recipients['groups']);

                $devices = $query->execute();
            }

            // создадим таску на отправку пуша для конкретного устройства пользователя

            foreach ($devices as $device) {

                $logParams = [
                    'token'        => $device['token'],
                    'user_id'      => $device['user_id'],
                    'notification' => $notification->getId()
                ];

                $this->logger->info('Постановка сообщения в очередь отправки', $logParams);

                try {

                    $this->producer->send(Processor::QUEUE_PUSH_DIRECT, [
                        'device_token' => $device['token'],
                        'user_id'      => $device['user_id'],
                        'message'      => $notification->getMessage(),
                        'data'         => $notification->getData()
                    ]);

                } catch (\Exception $ex) {

                    $this->logger->error(
                        'Ошибка при постановке в очередь уведомления: ' . $ex->getMessage(),
                        array_merge($logParams, ['error' => $ex->getMessage()])
                    );

                    continue;
                }
            }

            $notification->setStatus(PushNotificationStatusEnum::SUCCESS());

        } catch (\Exception $ex) {

            $this->logger->error('Ошибка при отправке уведомления: ' . $ex->getMessage(), [
                'error' => $ex->getMessage(),
                'notification' => $notificationId
            ]);

            $notification->setStatus(PushNotificationStatusEnum::ERROR());
        }

        $this->em->persist($notification);
        $this->em->flush();

        return PsrProcessor::ACK;
    }
}