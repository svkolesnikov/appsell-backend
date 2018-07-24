<?php

namespace App\Controller\Admin;

use App\DataSource\EmployeeOfferDataSource;
use App\DataSource\SellerOfferDataSource;
use App\Entity\ForOfferCommission;
use App\Entity\Offer;
use App\Entity\SellerApprovedOffer;
use App\Form\OfferType;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OfferController extends BaseController
{
    /** @var EntityManager */
    protected $em;

    /** @var  UserGroupManager */
    protected $userGroupManager;

    /** @var  SellerOfferDataSource */
    protected $sellerOfferDataSource;

    /** @var  EmployeeOfferDataSource */
    protected $employeeOfferDataSource;

    public function __construct(EntityManagerInterface $em,
                                UserGroupManager $userGroupManager,
                                SellerOfferDataSource $sds,
                                EmployeeOfferDataSource $eds)
    {
        $this->em = $em;
        $this->userGroupManager = $userGroupManager;
        $this->sellerOfferDataSource = $sds;
        $this->employeeOfferDataSource = $eds;
    }

    protected function checkAccess(Offer $offer)
    {
        if ( ! $this->isGranted('ROLE_SUPER_ADMIN') && $offer->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'Доступ запрещен!');
            return $this->redirectToRoute('app_offer_list');
        }
    }

    /**
     * @Route("/admin/offers", name="app_offer_list")
     *
     * @Security("has_role('ROLE_APP_OFFER_LIST')")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \LogicException
     * @throws \UnexpectedValueException
     */
    public function listAction(Request $request): Response
    {
        $page            = $request->get('_page', 1);
        $perPage         = $request->get('_per_page', 16);
        $offset          = ($page-1) * $perPage;
        $criteria        = [];
        $items           = [];
        $commissions     = [];
        $availableOffers = [];

        try {

            // получение данных для админа
            if ($this->isGranted('ROLE_ADMIN')) {
                $items = $this->em->getRepository(Offer::class)->findBy($criteria, [], $perPage, $offset);

                foreach ($items as $item) {
                    $commissions[$item->getId()] = $this->em
                        ->getRepository(ForOfferCommission::class)
                        ->findOneBy(['offer' => $item, 'by_user' => null]);
                }
            }

            // для заказчика
            elseif ($this->userGroupManager->hasGroup($this->getUser(), UserGroupEnum::OWNER())) {

                $criteria['owner']      = $this->getUser();
                $criteria['is_deleted'] = false;

                $items = $this->em->getRepository(Offer::class)->findBy($criteria, [], $perPage, $offset);
            }

            // для продавца
            elseif ($this->userGroupManager->hasGroup($this->getUser(), UserGroupEnum::SELLER())) {

                // получим офферы продавца с соответствующими комиссиями
                $items = $this->sellerOfferDataSource->getAvailableOffers($this->getUser(), $perPage, $offset);

                foreach ($items as $item) {

                    $availableOffers[$item->id] = $this->em
                        ->getRepository(SellerApprovedOffer::class)
                        ->createQueryBuilder('s')
                        ->innerJoin('s.offer', 'o')
                        ->innerJoin('s.seller', 'u')
                        ->where('o.id = :offer_id AND u.id = :user_id')
                        ->setParameter('offer_id', $item->id)
                        ->setParameter('user_id', $this->getUser()->getId())
                        ->getQuery()
                        ->getOneOrNullResult()
                    ;

                    $commissions[$item->id] = $this->em
                        ->getRepository(ForOfferCommission::class)
                        ->createQueryBuilder('c')
                        ->innerJoin('c.offer', 'o')
                        ->where('o.id = :offer_id AND c.by_user = :by_user')
                        ->setParameter('offer_id', $item->id)
                        ->setParameter('by_user', $this->getUser())
                        ->getQuery()
                        ->getOneOrNullResult()
                    ;
                }
            }

            // для сотрудника продавца
            elseif ($this->userGroupManager->hasGroup($this->getUser(), UserGroupEnum::EMPLOYEE())) {
                $items = $this->employeeOfferDataSource->getAvailableOffers($this->getUser(), $perPage, $offset);
            }

        } catch (\Exception $ex) {
            $this->addFlash('error', 'Не удалось получить список офферов для пользователя. ' . $ex->getMessage());
        }

        return $this->render('pages/offer/list.html.twig', [
            'available_offers' => $availableOffers,
            'commissions'      => $commissions,
            'offers'           => $items,
            'pager'            => [
                '_per_page'    => $perPage,
                '_page'        => $page,
                '_has_more'    => \count($items) >= $perPage
            ]
        ]);
    }

    /**
     * @Route("/admin/offers/{id}/edit", name="app_offer_edit")
     *
     * @Security("has_role('ROLE_APP_OFFER_EDIT')")
     *
     * @param Request $request
     *
     * @param Offer $offer
     * @return Response
     */
    public function editAction(Request $request, Offer $offer): Response
    {
        if ( ! $this->isGranted('ROLE_SUPER_ADMIN') && $offer->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'Доступ запрещен!');
            return $this->redirectToRoute('app_offer_list');
        }

        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                $this->em->persist($offer);
                $this->em->flush();

                $this->addFlash('success', 'Запись обновлена');

                return $this->redirectToRoute('app_offer_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при обновлении записи: ' . $ex->getMessage());
            }
        }

        return $this->render('pages/offer/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'edit'
        ]);
    }

    /**
     * @Route("/admin/offers/create", name="app_offer_create")
     *
     * @Security("has_role('ROLE_APP_OFFER_CREATE')")
     *
     * @param Request $request
     * @return Response
     * @throws \LogicException
     */
    public function createAction(Request $request): Response
    {
        $offer  = new Offer();
        $offer->setOwner($this->getUser());

        $form   = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                $this->em->persist($offer);
                $this->em->flush();

                $this->addFlash('success', 'Запись создана');

                return $this->redirectToRoute('app_offer_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при добавлении записи: ' . $ex->getMessage());
            }
        }

        return $this->render('pages/offer/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'create'
        ]);
    }

    /**
     * @Route("/admin/offers/{id}/remove", name="app_offer_remove")
     *
     * @Security("has_role('ROLE_APP_OFFER_DELETE')")
     *
     * @param Offer $offer
     * @return Response
     */
    public function removeAction(Offer $offer): Response
    {
        if ( ! $this->isGranted('ROLE_SUPER_ADMIN') && $offer->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'Доступ запрещен!');
            return $this->redirectToRoute('app_offer_list');
        }

        try {

            $this->em->remove($offer);
            $this->em->flush();

            $this->addFlash('success', 'Запись успешно удалена');

        } catch (\Exception $ex) {
            $this->addFlash('error', 'Не удалось удалить запись. ' . $ex->getMessage());
        }

        return $this->redirectToRoute('app_offer_list');
    }

    /**
     * @Route("/admin/offers/{id}/activity", name="app_offer_change_activity")
     *
     * @Security("has_role('ROLE_APP_OFFER_CHANGE_ACTIVITY')")
     *
     * @param Offer $offer
     * @return Response
     */
    public function changeActivityAction(Request $request, Offer $offer): Response
    {
        $this->checkAccess($offer);

        try {

            if ((bool)$request->get('active')) {
                $now = new \DateTime();

                $errors = [];

                if (0 === \count($offer->getLinks())) {
                    $errors[] = 'отсутствуют ссылки';
                }

                if (0 === \count($offer->getCompensations())) {
                    $errors[] = 'отсутствуют компенсации';
                }

                if ($offer->getActiveFrom() > $now || $offer->getActiveTo() < $now) {
                    $errors[] = 'неверный срок оффера';
                }

                if (0 !== \count($errors)) {
                    throw new \Exception('Причины: ' . implode(', ', $errors));
                }
            }

            $offer->setActive((bool)$request->get('active'));

            $this->em->persist($offer);
            $this->em->flush();

            $this->addFlash('success', 'Активность оффера изменена');

        } catch (\Exception $ex) {
            $this->addFlash('error', 'Не удалось изменить активность записи. ' . $ex->getMessage());
        }

        return $this->redirectToRoute('app_offer_list');
    }

    /**
     * @Route("/admin/offers/{id}/accessibility/{action}", name="app_offer_change_accessibility")
     *
     * @Security("has_role('ROLE_APP_OFFER_CHANGE_ACCESSIBILITY')")
     *
     * @param Request $request
     * @param Offer $offer
     * @return Response
     */
    public function changeAccessibilityAction(Request $request, Offer $offer): Response
    {
        // Проверим наличие уже созданного разрешения

        $approve = $this->em
            ->getRepository(SellerApprovedOffer::class)
            ->findOneBy([
                'offer'  => $offer,
                'seller' => $this->getUser()
            ]);

        try {

            if ('approve' === $request->get('action', 'approve')) {
                $approve = $approve ?? (new SellerApprovedOffer())->setOffer($offer)->setSeller($this->getUser());
                $this->em->persist($approve);

            } else {
                $this->em->remove($approve);
            }

            $this->em->flush();

            $this->addFlash('success', 'Доступность оффера изменена');

        } catch (\Exception $ex) {
            $this->addFlash('error', 'Не удалось изменить активность записи. ' . $ex->getMessage());
        }

        return $this->redirectToRoute('app_offer_list');
    }

    /**
     * @Route("/admin/offers/{id}/hide", name="app_offer_hide")
     *
     * @Security("has_role('ROLE_APP_OFFER_HIDE')")
     *
     * @param Offer $offer
     * @return Response
     */
    public function hideAction(Offer $offer): Response
    {
        $this->checkAccess($offer);

        try {

            $offer->setDeleted(true);

            $this->em->persist($offer);
            $this->em->flush();

            $this->addFlash('success', 'Запись удалена');

        } catch (\Exception $ex) {
            $this->addFlash('error', 'Не удалось удалить запись. ' . $ex->getMessage());
        }

        return $this->redirectToRoute('app_offer_list');
    }
}