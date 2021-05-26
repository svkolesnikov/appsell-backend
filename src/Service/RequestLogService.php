<?php

namespace App\Service;

use App\Entity\RequestLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RequestLogService
{
    private EntityManagerInterface $em;

    public function __construct (EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function add(string $rquid, string $message, array $header, string $params, object $response)
    {
//        ???????????????????????????????????????????????????????????????????
        $repository = $this->em->getRepository(RequestLog::class);

        $log = new RequestLog();
        $log->setRqUid($rquid);
        $log->setMessage($message);
        $log->setRequestData(['header' => $header, 'params' => $params]);
        $log->setResponseData(json_decode(json_encode($response), true));
        $log->setCtime(new \DateTime());
        $log->setMtime(new \DateTime());

        $this->em->persist($log);
        $this->em->flush($log);
    }
}
