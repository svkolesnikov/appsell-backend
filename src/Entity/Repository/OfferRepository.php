<?php

namespace App\Entity\Repository;

use App\Entity\Offer;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Offer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Offer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Offer[]    findAll()
 * @method Offer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferRepository extends EntityRepository
{
    public function checkPayQr(string $offer_id): bool
    {
        return $this->find($offer_id)->isPayQr();
    }

    public function check(string $offer_id): bool
    {
        return strlen($offer_id) === 36 && is_object($this->findOneBy(['id' => $offer_id]));
    }
}
