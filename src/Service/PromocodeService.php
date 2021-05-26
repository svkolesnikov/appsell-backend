<?php

namespace App\Service;

use App\Entity\Promocode;
use Doctrine\ORM\EntityManagerInterface;

class PromocodeService
{
  private EntityManagerInterface $em;
  private PromocodeLogService $log;

  public function __construct (EntityManagerInterface $entityManager)
  {
      $this->em  = $entityManager;
      $this->log = new PromocodeLogService($entityManager);
  }

    public function get(string $seller_id, string $company_id)
    {
        $repository = $this->em->getRepository(Promocode::class);

        $promocode = $repository->getOne($seller_id, $company_id);

        if ($promocode) {
            $this->log->add('Промокод ' . $promocode->getCode() . ' от компании с id ' . $company_id . ' отдан продавцу ' . $seller_id . '.');
        }

        return ($promocode) ? $promocode : false;
    }
}
