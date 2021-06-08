<?php

namespace App\Entity\Repository;

use App\Entity\UserProfile;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserProfile[]    findAll()
 * @method UserProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserProfileRepository extends EntityRepository
{
    public function checkIdQr(string $seller_id): bool
    {
        return $this->find($seller_id)->getIdQr() !== null;
    }

    public function check(string $seller_id): bool
    {
        return strlen($seller_id) === 36 && is_object($this->find($seller_id));
    }
}
