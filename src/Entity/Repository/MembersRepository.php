<?php

namespace App\Entity\Repository;

use App\Entity\Members;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Members|null find($id, $lockMode = null, $lockVersion = null)
 * @method Members|null findOneBy(array $criteria, array $orderBy = null)
 * @method Members[]    findAll()
 * @method Members[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MembersRepository extends EntityRepository
{
	public function getNewMember ()
	{
		$member = new Members();
            	$this->getEntityManager()->persist($member);
            	$this->getEntityManager()->flush($member);

		return $member;
	}

	public function setPaymentId (Members $member, string $payment_id)
	{
        	$member->setPaymentId($payment_id);
       	 	$this->getEntityManager()->flush($member);
	}
}
