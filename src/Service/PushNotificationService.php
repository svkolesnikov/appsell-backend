<?php

namespace App\Service;

use App\Entity\Offer;
use App\Entity\PushNotification;
use App\Entity\User;
use App\Lib\Enum\PushNotificationStatusEnum;
use App\Queue\Processor\Processor;
use App\Queue\Producer\Producer;
use Doctrine\ORM\EntityManagerInterface;

class PushNotificationService
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var Producer */
    protected $producer;

    public function __construct(EntityManagerInterface $em, Producer $p)
    {
        $this->entityManager  = $em;
        $this->producer       = $p;
    }

    public function create($message, User $sender, array $recipients, ?Offer $offer = null): PushNotification
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
        $notification->setStatus(PushNotificationStatusEnum::NEW());
        $notification->setRecipients($recipients);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        try {

            $this->producer->send(Processor::QUEUE_PUSH_COMMON, [
                'notification' => $notification->getId()
            ]);

        } catch (\Exception $ex) {

            $this->entityManager->remove($notification);
            $this->entityManager->flush();

            throw $ex;
        }

        return $notification;
    }
}