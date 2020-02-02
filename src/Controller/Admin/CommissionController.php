<?php

namespace App\Controller\Admin;

use App\Entity\BaseCommission;
use App\Form\CommissionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommissionController extends BaseController
{
    /** @var  EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/admin/commissions", name="app_commission_list")
     *
     * @Security("is_granted('ROLE_APP_COMMISSION_LIST')")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \UnexpectedValueException
     */
    public function listAction(Request $request): Response
    {
        $page = $request->get('_page', 1);
        $perPage = $request->get('_per_page', 16);
        $offset =  ($page-1) * $perPage;

        try {
            $items = $this->em->getRepository(BaseCommission::class)->findBy([], [], $perPage, $offset);
        } catch (\Exception $ex) {
            $this->addFlash('error', 'Не удалось получить список базовых комиссий. ' . $ex->getMessage());
        }

        return $this->render('pages/commission/list.html.twig', [
            'commissions' => $items,
            'pager' => [
                '_per_page' => $perPage,
                '_page'     => $page,
                '_has_more' => count($items) >= $perPage
            ]
        ]);
    }

    /**
     * @Route("/admin/commissions/{id}/edit", name="app_commission_edit")
     *
     * @Security("is_granted('ROLE_APP_COMMISSION_EDIT')")
     *
     * @param Request $request
     *
     * @param BaseCommission $commission
     * @return Response
     */
    public function editAction(Request $request, BaseCommission $commission): Response
    {
        $form = $this->createForm(CommissionType::class, $commission);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                $this->em->persist($commission);
                $this->em->flush();

                $this->addFlash('success', 'Запись обновлена');

                return $this->redirectToRoute('app_commission_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при обновлении записи: ' . $ex->getMessage());
            }
        }

        return $this->render('pages/commission/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'edit'
        ]);
    }

    /**
     * @Route("/admin/commissions/create", name="app_commission_create")
     *
     * @Security("is_granted('ROLE_APP_COMMISSION_CREATE')")
     *
     * @param Request $request
     * @return Response
     * @throws \LogicException
     */
    public function createAction(Request $request): Response
    {
        $commission = new BaseCommission();
        $form       = $this->createForm(CommissionType::class, $commission);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                $this->em->persist($commission);
                $this->em->flush();

                $this->addFlash('success', 'Запись создана');

                return $this->redirectToRoute('app_commission_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при добавлении записи: ' . $ex->getMessage());
            }
        }

        return $this->render('pages/commission/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'create'
        ]);
    }

    /**
     * @Route("/admin/commissions/{id}/remove", name="app_commission_remove")
     *
     * @Security("is_granted('ROLE_APP_COMMISSION_DELETE')")
     *
     * @param BaseCommission $commission
     * @return Response
     */
    public function removeAction(BaseCommission $commission): Response
    {
        try {

            $this->em->remove($commission);
            $this->em->flush();

            $this->addFlash('success', 'Запись успешно удалена');

        } catch (\Exception $ex) {
            $this->addFlash('error', 'Ошибка при удалении записи: ' . $ex->getMessage());
        }

        return $this->redirectToRoute('app_ commission_list');
    }
}