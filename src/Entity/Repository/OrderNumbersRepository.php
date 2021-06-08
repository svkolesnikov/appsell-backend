<?php

namespace App\Entity\Repository;

use App\Entity\OrderNumbers;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OrderNumbers|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderNumbers|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderNumbers[]    findAll()
 * @method OrderNumbers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderNumbersRepository extends EntityRepository
{
	public function getNewNumber ()
	{
		$order_number = new OrderNumbers();
            	$this->getEntityManager()->persist($order_number);
            	$this->getEntityManager()->flush($order_number);

		return $order_number;
	}

	public function setPaymentId (OrderNumbers $order_number, string $payment_id)
	{
        	$order_number->setPaymentId($payment_id);
       	 	$this->getEntityManager()->flush($order_number);
	}

}
