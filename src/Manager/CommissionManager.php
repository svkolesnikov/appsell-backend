<?php

namespace App\Manager;

use App\Entity\SellerApprovedOffer;
use App\Entity\SellerBaseCommission;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class CommissionManager
{
    /** @var EntityManager */
    protected $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    public function getSellerBaseCommission(User $user)
    {
        return $this->entityManager
            ->getRepository(SellerBaseCommission::class)
            ->findOneBy(['seller' => $user]);
    }

    public function updateSellerBaseCommission(User $user, $percent): void
    {
        $commission = $this->entityManager
            ->getRepository(SellerBaseCommission::class)
            ->findOneBy(['seller' => $user]);

        $commission = $commission ?? (new SellerBaseCommission())->setSeller($user);
        $commission->setPercent($percent);

        $this->save($commission);
    }

    public function save($object)
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush();
    }
}