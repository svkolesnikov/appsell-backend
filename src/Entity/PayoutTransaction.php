<?php

namespace App\Entity;

use App\Lib\Enum\PayoutDestinationEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\Repository\PayoutTransactionRepository")
 * @ORM\Table(name="financedata.payout_transaction")
 * @ORM\HasLifecycleCallbacks
 */
class PayoutTransaction
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity = "User", fetch="EAGER")
     * @ORM\JoinColumn(name = "receiver_user_id", referencedColumnName = "id")
     */
    protected $receiver;

    /**
     * @ORM\Column(type="integer")
     */
    protected $amount;

    /**
     * @ORM\Column(type="string")
     */
    protected $destination;

    /**
     * @ORM\Column(type="string")
     */
    protected $info;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $ctime;

    public function __construct()
    {
        $this->info = json_encode([]);
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        $this->ctime = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getReceiver(): User
    {
        return $this->receiver;
    }

    public function setReceiver(User $receiver)
    {
        $this->receiver = $receiver;
        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return PayoutDestinationEnum
     * @throws \UnexpectedValueException
     */
    public function getDestination(): PayoutDestinationEnum
    {
        return new PayoutDestinationEnum($this->destination);
    }

    public function setDestination(PayoutDestinationEnum $destination)
    {
        $this->destination = $destination->getValue();
        return $this;
    }

    public function getInfo(): array
    {
        return (array) json_decode($this->info);
    }

    public function setInfo(array $info)
    {
        $this->info = json_encode($info);
        return $this;
    }

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }
}