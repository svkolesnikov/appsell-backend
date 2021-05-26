<?php

namespace App\Service;

use App\Entity\PromocodeLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PromocodeLogService
{
    private EntityManagerInterface $em;

    public function __construct (EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }
    public function add($message)
    {
//        ???????????????????????????????????????????????????????????????????
        $repository = $this->em->getRepository(PromocodeLog::class);

        $log = new PromocodeLog();
        $log->setMessage($message);
        $log->setCtime(new \DateTime());

        $this->em->persist($log);
        $this->em->flush($log);
    }
}
