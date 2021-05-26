<?php

namespace App\Entity\Repository;

use App\Entity\Companies;
use Doctrine\ORM\EntityRepository;

/**
 * @method Companies|null find($id, $lockMode = null, $lockVersion = null)
 * @method Companies|null findOneBy(array $criteria, array $orderBy = null)
 * @method Companies[]    findAll()
 * @method Companies[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompaniesRepository extends EntityRepository
{
    public function existsCompany (string $id): bool
    {
        return preg_match('#^[0-9]+$#', $id) === 1 && $this->find($id) !== null;
    }
}
