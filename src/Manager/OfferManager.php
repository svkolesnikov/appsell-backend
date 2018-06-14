<?php

namespace App\Manager;

use App\Entity\Offer;
use App\Entity\SellerApprovedOffer;
use App\Entity\User;
use App\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class OfferManager
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var  UserGroupManager */
    protected $userManager;

    public function __construct(EntityManagerInterface $em, UserGroupManager $userGroupManager)
    {
        $this->entityManager = $em;
        $this->userManager = $userGroupManager;
    }

    public function getList(User $user, $criteria, $perPage, $offset): array
    {
        // "Сотрудник" видит разрешенные не "удаленные" офферы
        if ($this->userManager->hasGroup($user, UserGroupEnum::EMPLOYEE())) {

            $employer = $user->getProfile()->getEmployer();
            $criteria = array_merge($criteria, [
                'seller'            => $employer,
                'offer.is_active'   => true,
                'offer.is_deleted'  => false
            ]);

            $items = $this->entityManager
                ->getRepository(SellerApprovedOffer::class)
                ->findBy($criteria, [], $perPage, $offset);

        } else {

            // "Заказчик" видит только свои не "удаленные" офферы
            if ($this->userManager->hasGroup($user, UserGroupEnum::OWNER())) {
                $criteria['owner']      = $user;
                $criteria['is_deleted'] = false;
            }

            // "Продавец" видит все активные не "удаленные" офферы
            else if ($this->userManager->hasGroup($user, UserGroupEnum::SELLER())) {
                $criteria['is_active']  = true;
                $criteria['is_deleted'] = false;
            }

            $items = $this->entityManager
                ->getRepository(Offer::class)
                ->findBy($criteria, [], $perPage, $offset);
        }

        return $items;
    }

    public function save(Offer $offer): void
    {
        $this->entityManager->persist($offer);
        $this->entityManager->flush();
    }

    public function remove(Offer $offer): void
    {
        $this->entityManager->remove($offer);
        $this->entityManager->flush();
    }

    public function hide(Offer $offer): void
    {
        $offer->setDeleted(true);

        $this->save($offer);
    }

    public function changeActivity(Offer $offer, bool $active): Offer
    {
        $offer->setActive($active);
        $this->save($offer);

        return $offer;
    }
}