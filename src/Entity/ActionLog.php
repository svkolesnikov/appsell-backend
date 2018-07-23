<?php

namespace App\Entity;

use App\Lib\Enum\ActionLogItemTypeEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="actiondata.action_log")
 * @ORM\HasLifecycleCallbacks
 */
class ActionLog
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @ORM\Column(type="string")
     */
    protected $message;

    /**
     * @ORM\Column(type="string")
     */
    protected $data;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $ctime;

    public function __construct()
    {
        $this->data = json_encode([]);
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        $this->ctime = new \DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return ActionLogItemTypeEnum
     * @throws \UnexpectedValueException
     */
    public function getType(): ActionLogItemTypeEnum
    {
        return new ActionLogItemTypeEnum($this->type);
    }

    public function setType(ActionLogItemTypeEnum $type)
    {
        $this->type = $type->getValue();
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
        return $this;
    }

    public function getData(): array
    {
        return (array) json_decode($this->data, true);
    }

    public function setData(array $data)
    {
        $this->data = json_encode($data);
        return $this;
    }

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }
}