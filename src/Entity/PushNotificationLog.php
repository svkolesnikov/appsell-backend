<?php

namespace App\Entity;

use App\Lib\Enum\ActionLogItemTypeEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="actiondata.push_notification_log")
 * @ORM\HasLifecycleCallbacks
 */
class PushNotificationLog
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var PushNotification
     * @ORM\ManyToOne(targetEntity = "PushNotification")
     * @ORM\JoinColumn(name = "push_notification_id", referencedColumnName = "id")
     */
    protected $notification;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "user_id", referencedColumnName = "id")
     */
    protected $user;

    /**
     * @var DevicePushToken
     * @ORM\ManyToOne(targetEntity = "DevicePushToken")
     * @ORM\JoinColumn(name = "device_id", referencedColumnName = "id")
     */
    protected $device;

    /**
     * @ORM\Column(type="string")
     */
    protected $multicast_id;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $success;

    /**
     * @ORM\Column(type="string")
     */
    protected $info;

    /**
     * @ORM\Column(type="string")
     */
    protected $error;

    public function getId()
    {
        return $this->id;
    }

    public function getNotification(): PushNotification
    {
        return $this->notification;
    }

    public function setNotification(PushNotification $notification)
    {
        $this->notification = $notification;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getDevice(): ?DevicePushToken
    {
        return $this->device;
    }

    public function setDevice(DevicePushToken $device)
    {
        $this->device = $device;
        return $this;
    }

    public function getMulticastId()
    {
        return $this->multicast_id;
    }

    public function setMulticastId($multicast_id)
    {
        $this->multicast_id = $multicast_id;
        return $this;
    }

    public function getSuccess()
    {
        return $this->success;
    }

    public function setSuccess($success)
    {
        $this->success = $success;
        return $this;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function setInfo($info)
    {
        $this->info = $info;
        return $this;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }
}