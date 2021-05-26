<?php

namespace App\Entity\Repository;

use App\Entity\Payments;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Payments|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payments|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payments[]    findAll()
 * @method Payments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentsRepository extends EntityRepository
{
    public function createNewPayment(array $order)
    {
        $payment = new Payments();
        $payment->setRquid($order['rq_uid']);
        $payment->setRqtm(\DateTime::createFromFormat('Y-m-d\TH:i:s', substr($order['rq_tm'], 0, -1)));
        $payment->setCompanyId($order['company_id']);
        $payment->setMemberId($order['member_id']);
        $payment->setOrderNumber($order['order_number']);
        $payment->setOrderCreateDate(\DateTime::createFromFormat('Y-m-d\TH:i:s',  substr($order['order_create_date'], 0, -1)));
        $payment->setPositionName($order['position_name']);
        $payment->setPositionCount($order['position_count']);
        $payment->setPositionSum($order['position_sum']);
        $payment->setPositionDescription($order['position_description']);
        $payment->setIdQr($order['id_qr']);
        $payment->setOrderSum($order['order_sum']);
        $payment->setCurrency($order['currency']);
        $payment->setOrderDescription($order['order_description']);
        $payment->setSellerId($order['seller_id']);
        $payment->setCtime(new \DateTime());
        $payment->setStatus(0);

        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush($payment);

        return $payment;
    }

   public function addSberbankResponse(Payments $payment, object $response)
    {
	$payment->setOrderId($response->status->order_id);
        $payment->setOrderFromUrl($response->status->order_form_url);
        $payment->setMtime(new \DateTime());

        $this->getEntityManager()->flush($payment);

        return $payment;
    }

    public function markPaid (Payments $payment)
    {
        $payment->setMtime(new \Datetime());
        $payment->setStatus(1);

        $this->getEntityManager()->flush($payment);
    }

    public function markRevoked (Payments $payment)
    {
        $payment->setMtime(new \Datetime());
        $payment->setStatus(2);

        $this->getEntityManager()->flush($payment);
    }

    public function addPromocodeToPayment (Payments $payment, int $promocode)
    {
        $payment->setPromocodeId($promocode);

        $this->getEntityManager()->flush($payment);
    }

    public function findPaymentInProcess(): array
    {
        return $this->createQueryBuilder('payments')
            ->andWhere('payments.status = :status')
	    ->andWhere('payments.order_id is not NULL')
            ->setParameter('status', 0)
            ->orderBy('payments.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForRevoke(string $seller_id, string $order_id)
    {
        $payment = $this->createQueryBuilder('payments')
            ->andWhere('payments.seller_id = :seller_id')
            ->andWhere('payments.order_id = :order_id')
            ->andWhere('payments.status = :status')
            ->setParameter('seller_id', $seller_id)
            ->setParameter('order_id', $order_id)
            ->setParameter('status', 0)
            ->orderBy('payments.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()[0];

        return ($payment !== null) ? $payment : false;
    }

    public function checkPayment(string $seller_id, string $order_id)
    {
        $payment = $this->createQueryBuilder('payments')
            ->andWhere('payments.seller_id = :seller_id')
            ->andWhere('payments.order_id = :order_id')
            ->setParameter('seller_id', $seller_id)
            ->setParameter('order_id', $order_id)
	    ->orderBy('payments.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()[0];
	
        return ($payment !== null) ? $payment : false;
    }

    public function setPromocodeIdNull(string $promodoce_id): bool
    {
        $payment = $this->createQueryBuilder('payments')
            ->andWhere('payments.promocode_id = :promocode_id')
            ->setParameter('promocode_id', $promodoce_id)
            ->getQuery()
            ->getResult()[0];

        if ($payment !== null) {
            $payment->setPromocodeId(null);
            $this->getEntityManager()->flush($payment);
        }

        return ($payment !== null);
    }

    public function findAllNotPaidYet(): array
    {
        return $this->createQueryBuilder('payments')
            ->andWhere('payments.status = :status')
	    ->andWhere('payments.ctime < :time')
            ->setParameter('status', 0)
	    ->setParameter('time', gmdate('Y-m-d H:i:s', time() - 3600))
            ->orderBy('payments.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
