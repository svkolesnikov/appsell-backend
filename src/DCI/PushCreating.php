<?php

namespace App\DCI;

use Psr\Container\ContainerInterface;
use RedjanYm\FCMBundle\FCMClient;

class PushCreating
{
    /** @var FCMClient */
    protected $fcmClient;

    public function __construct(ContainerInterface $c)
    {
        $this->fcmClient = $c->get('redjan_ym_fcm.client');
    }

    public function send($message, $data, $token, $priority = 'high')
    {
        $push = $this->fcmClient->createDeviceNotification();

        $push->setBody($message);
        $push->setData($data);
        $push->setPriority($priority);
        $push->setDeviceToken($token);

        $response = $this->fcmClient->sendNotification($push);

        return json_decode($response->getBody()->getContents(), true);
    }
}