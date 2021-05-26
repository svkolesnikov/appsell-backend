<?php

namespace App\Service;

use App\Entity\PaymentsLog;
use Doctrine\ORM\EntityManagerInterface;

class PaymentsLogService
{
    private EntityManagerInterface $em;

    public function __construct (EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function add($message)
    {
        $log = new PaymentsLog();
        $log->setMessage($message);
        $log->setCtime(new \DateTime());

        $this->em->persist($log);
        $this->em->flush($log);
    }
}
