<?php

namespace App\Controller\Admin;

use App\Entity\Offer;
use App\Form\OfferType;
use App\Manager\OfferManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OfferController extends BaseController
{
    /** @var  OfferManager */
    protected $offerManager;

    public function __construct(OfferManager $offerManager)
    {
        $this->offerManager = $offerManager;
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
        $page       = $request->get('_page', 1);
        $perPage    = $request->get('_per_page', 16);
        $offset     = ($page-1) * $perPage;
        $criteria   = [];

        $items = $this->offerManager->getList($this->getUser(), $criteria, $perPage, $offset);

        return $this->render('pages/offer/list.html.twig', [
            'offers' => $items,
            'pager' => [
                '_per_page' => $perPage,
                '_page'     => $page,
                '_has_more' => \count($items) >= $perPage
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

                $this->offerManager->save($offer);

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
        $form   = $this->createForm(OfferType::class, $offer);

        $offer->setOwner($this->getUser());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                $this->offerManager->save($offer);

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

        $this->offerManager->remove($offer);

        $this->addFlash('success', 'Запись успешно удалена');

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

        $this->offerManager->changeActivity($offer, $request->get('active'));

        $this->addFlash('success', 'Активность оффера изменена');

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

        $this->offerManager->hide($offer);

        $this->addFlash('success', 'Активность оффера изменена');

        return $this->redirectToRoute('app_offer_list');
    }
}