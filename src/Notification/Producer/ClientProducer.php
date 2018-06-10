<?php

namespace App\Notification\Producer;

use App\Enum\NotificationTypeEnum;
use App\Notification\Notificator\EmailNotificator;

class ClientProducer
{
    /** @var EmailNotificator */
    protected $notificator;

    /** @var string[] */
    protected $receivers;

    public function __construct(EmailNotificator $notificator)
    {
        $this->notificator = $notificator;
    }

    public function produce(NotificationTypeEnum $type, array $params): void
    {
        $this->notificator->send($type->getValue(), $params);
    }
}