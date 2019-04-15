<?php

namespace App\Queue\MessageHandler;

use App\Entity\DevicePushToken;
use App\Entity\PushNotification;
use App\Entity\PushNotificationLog;
use App\Entity\User;
use App\Lib\Enum\PushNotificationStatusEnum;
use App\Queue\Processor\Processor;
use App\Queue\Producer\Producer;
use Doctrine\ORM\EntityManagerInterface;
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

    public function __construct(EntityManagerInterface $em, LoggerInterface $l, Producer $p)
    {
        $this->em = $em;
        $this->logger = $l;
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
            $recipientsIds = [];

            $recipientsSource = json_decode($notification->getRecipients(), true);

            // в получателях могут быть как отдельные пользователи

            if (array_key_exists('users', $recipientsSource)) {
                $recipientsIds = array_merge($recipientsIds, $recipientsSource['users']);
            }

            // так и целые группы

            if (array_key_exists('groups', $recipientsSource)) {
                $qb = $this->em->createQueryBuilder();
                $users = $qb
                    ->select('u.id')
                    ->from(User::class, 'u')
                    ->innerJoin('u.groups', 'g')
                    ->where($qb->expr()->in('g.code', $recipientsSource['groups']))
                    ->getQuery()
                    ->getArrayResult();

                $recipientsIds = array_merge($recipientsIds, array_column($users, 'id'));
            }

            /** @var User $recipient */
            foreach ($recipientsIds as $recipientId) {

                $recipient = $this->em
                    ->getRepository(User::class)
                    ->findOneById($recipientId);

                if (null === $recipient) {
                    continue;
                }

                $devices = $recipient->getDevices();

                // если устройств нет, то запишем в лог ошибку

                if (empty($devices->toArray())) {

                    $log = new PushNotificationLog();
                    $log->setNotification($notification);
                    $log->setStatus(PushNotificationStatusEnum::ERROR());
                    $log->setUser($recipient);
                    $log->setError('Отсутствуют зарегистрированные устройства');

                    $this->em->persist($log);
                    $this->em->flush();

                    continue;
                }

                // а иначе, создадим таску на отправку пуша для конкретного устройства пользователя

                /** @var DevicePushToken $device */
                foreach ($devices as $device) {

                    $log = new PushNotificationLog();

                    $log->setStatus(PushNotificationStatusEnum::NEW());
                    $log->setUser($recipient);
                    $log->setDevice($device);
                    $log->setNotification($notification);

                    try {

                        $this->em->persist($log);
                        $this->em->flush();

                        $this->producer->send(Processor::QUEUE_PUSH_DIRECT, [
                            'message' => $notification->getMessage(),
                            'log'     => $log->getId(),
                            'data'    => $notification->getData()
                        ]);

                    } catch (\Exception $ex) {

                        $log->setStatus(PushNotificationStatusEnum::ERROR());
                        $log->setError('Ошибка при постановке уведомления в очередь на отправку');

                        $this->em->persist($log);
                        $this->em->flush();

                        $this->logger->error('Ошибка при постановке в очередь уведомления для пользователя: ' . $recipient->getId(), [
                            'error' => $ex->getMessage(),
                            'user' => $recipient->getId(),
                            'notification' => $notificationId
                        ]);

                        continue;
                    }
                }
            }

            $notification->setStatus(PushNotificationStatusEnum::SUCCESS());

        } catch (\Exception $ex) {

            $this->logger->error('Ошибка при отправке уведомления', [
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