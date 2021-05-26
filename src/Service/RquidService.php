<?php

namespace App\Service;

use App\Entity\RequestLog;
use Doctrine\ORM\EntityManagerInterface;

class RquidService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function get (): string
    {
        $repository = $this->em->getRepository(RequestLog::class);
        do {
            $rquid = $this->generate();
        } while (! $repository->check($rquid) ?? $rquid !== null);

        return $rquid;
    }

    private function generate (): string
    {
        $letters = [
            'a', 'b', 'c', 'd', 'e', 'f', 'A', 'B', 'C', 'D', 'E', 'F', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
        ];

        $rquid = '';
        while(strlen($rquid) != 32) {
            $rquid .= $letters[rand(0, count($letters) - 1)];
        }

        return $rquid;
    }
}
