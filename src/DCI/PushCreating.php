<?php

namespace App\DCI;

use App\Entity\ActionLog;
use App\Entity\DevicePushToken;
use App\Entity\Offer;
use App\Entity\PushNotification;
use App\Entity\PushNotificationLog;
use App\Entity\User;
use App\Exception\Admin\AdminException;
use App\Exception\AppException;
use App\Form\PushNotificationType;
use App\Lib\Enum\ActionLogItemTypeEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RedjanYm\FCMBundle\FCMClient;
use sngrl\PhpFirebaseCloudMessaging\Notification;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use Symfony\Component\HttpFoundation\Request;

class PushCreating
{
    /** @var EntityManagerInterface|EntityManager */
    protected $notificationManager;

    /** @var FCMClient */
    protected $fcmClient;

    /** @var UserGroupManager */
    protected $userGroupManager;

    public function __construct(EntityManagerInterface $em, ContainerInterface $c, UserGroupManager $gm)
    {
        $this->entityManager = $em;
        $this->userGroupManager = $gm;
        $this->fcmClient = $c->get('redjan_ym_fcm.client');
    }

    public function send($message, User $sender, array $recipients, ?Offer $offer = null): void
    {
        // инфа для маршрутизации внутри приложения
        $data = [];
        if (null !== $offer) {
            $data['offer'] = $offer->getId();
        }

        // сформируем уведомление
        $notification = new PushNotification();
        $notification->setSender($sender);
        $notification->setOffer($offer);
        $notification->setMessage($message);
        $notification->setData(json_encode($data));

        // попробуем отправить уведомления

        /** @var User $recipient */
        foreach ($recipients as $recipient) {

            $devices = $recipient->getDevices();

            // если устройств нет, то запишем в лог ошибку

            if (empty($devices->toArray())) {

                $log = new PushNotificationLog();
                $log->setUser($recipient);
                $log->setSuccess(false);
                $log->setError('Отсутствуют зарегистрированные устройства');

                $notification->addLog($log);

                continue;
            }

            // а иначе, отправим на все устройства пуши

            /** @var DevicePushToken $device */
            foreach ($devices as $device) {

                // сформируем push-уведомление
                $push = $this->fcmClient->createDeviceNotification();
                $push->setBody($message);
                $push->setData($data);
                $push->setPriority('high');
                $push->setDeviceToken($device->getToken());

                $response = $this->fcmClient->sendNotification($push);

                $data = json_decode($response->getBody()->getContents(), true);

                $log = new PushNotificationLog();
                $log->setUser($recipient);
                $log->setDevice($device);
                $log->setMulticastId($data['multicast_id']);
                $log->setSuccess($data['success']);

                if (1 === $data['success']) {
                    $log->setInfo($data['results'][0]['message_id']);
                } else {
                    $log->setError($data['results'][0]['error']);
                }

                $notification->addLog($log);
            }
        }

        if (0 === \count($notification->getLogs()->toArray())) {
            throw new AppException('У получателей отсутствуют зарегистрированные устройства!');
        }

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }
}