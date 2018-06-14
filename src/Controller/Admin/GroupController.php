<?php

namespace App\Controller\Admin;

use App\Controller\Admin\BaseController;
use App\Entity\Group;
use App\Form\GroupType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GroupController extends BaseController
{
    /** @var  EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/admin/groups", name="app_settings_groups_list")
     *
     * @Security("has_role('ROLE_APP_GROUP_LIST')")
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

        $items = $this->em->getRepository(Group::class)->findBy([], [], $perPage, $offset);

        return $this->render('pages/group/list.html.twig', [
            'groups' => $items,
            'pager' => [
                '_per_page' => $perPage,
                '_page'     => $page,
                '_has_more' => count($items) >= $perPage
            ]
        ]);
    }

    /**
     * @Route("/admin/groups/{id}/edit", name="app_settings_groups_edit")
     *
     * @Security("has_role('ROLE_APP_GROUP_EDIT')")
     *
     * @param Request $request
     *
     * @param Group $group
     * @return Response
     */
    public function editAction(Request $request, Group $group): Response
    {
        $form = $this->createForm(GroupType::class, $group);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                $this->em->persist($group);
                $this->em->flush();

                $this->addFlash('success', 'Группа обновлена');

                return $this->redirectToRoute('app_settings_groups_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при обновлении группы: ' . $ex->getMessage());
            }
        }

        return $this->render('pages/group/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'edit'
        ]);
    }

    /**
     * @Route("/admin/groups/create", name="app_settings_groups_create")
     *
     * @Security("has_role('ROLE_APP_GROUP_CREATE')")
     *
     * @param Request $request
     * @return Response
     * @throws \LogicException
     */
    public function createAction(Request $request): Response
    {
        $group  = new Group();
        $form   = $this->createForm(GroupType::class, $group);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {

                $this->em->persist($group);
                $this->em->flush();

                $this->addFlash('success', 'Группа создана');

                return $this->redirectToRoute('app_settings_groups_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при добавлении группы: ' . $ex->getMessage());
            }
        }

        return $this->render('pages/group/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'create'
        ]);
    }

    /**
     * @Route("/admin/groups/{id}/remove", name="app_settings_groups_remove")
     *
     * @Security("has_role('ROLE_APP_GROUP_DELETE')")
     *
     * @param Group $group
     * @return Response
     * @throws \LogicException
     */
    public function removeAction(Group $group): Response
    {
        $this->em->remove($group);
        $this->em->flush();

        $this->addFlash('success', 'Группа успешно удалена');

        return $this->redirectToRoute('app_settings_groups_list');
    }
}