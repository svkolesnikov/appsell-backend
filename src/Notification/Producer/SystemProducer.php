<?php

namespace App\Notification\Producer;

use App\Enum\NotificationTypeEnum;
use App\Notification\Notificator\EmailNotificator;

class SystemProducer
{
    /** @var EmailNotificator */
    protected $notificator;

    /** @var string[] */
    protected $receivers;

    public function __construct(EmailNotificator $notificator, array $receivers)
    {
        $this->notificator = $notificator;
        $this->receivers = $receivers;
    }

    public function produce(NotificationTypeEnum $type, array $params): void
    {
        $params['to'] = $this->receivers;
        $this->notificator->send($type->getValue(), $params);
    }
}