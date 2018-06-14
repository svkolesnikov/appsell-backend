<?php

namespace App\Controller\Admin;

use App\Controller\Admin\BaseController;
use App\Entity\Group;
use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends BaseController
{
    /** @var  EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/admin/users", name="app_settings_users_list")
     *
     * @Security("has_role('ROLE_APP_USER_LIST')")
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

        $users = $this->em->getRepository(User::class)->findBy([], [], $perPage, $offset);

        return $this->render('pages/user/list.html.twig', [
            'users' => $users,
            'pager' => [
                '_per_page' => $perPage,
                '_page'     => $page,
                '_has_more' => count($users) >= $perPage
            ]
        ]);
    }

    /**
     * @Route("/admin/users/{id}/edit", name="app_settings_users_edit")
     *
     * @Security("has_role('ROLE_APP_USER_EDIT')")
     *
     * @param Request $request
     *
     * @param User $user
     * @return Response
     */
    public function editAction(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->em->persist($user);
                $this->em->flush();

                $this->addFlash('success', 'Пользователь обновлен');

                return $this->redirectToRoute('app_settings_users_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при обновлении пользователя: ' . $ex->getMessage());
            }
        }

        $groups = $this->em->getRepository(Group::class)->findAll();

        return $this->render('pages/user/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'edit',
            'groups' => $groups
        ]);
    }

    /**
     * @Route("/admin/users/create", name="app_settings_users_create")
     *
     * @Security("has_role('ROLE_APP_USER_CREATE')")
     *
     * @param Request $request
     * @return Response
     * @throws \LogicException
     */
    public function createAction(Request $request): Response
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->em->persist($user);
                $this->em->flush();

                $this->addFlash('success', 'Пользователь обновлен');

                return $this->redirectToRoute('app_settings_users_list');

            } catch (\Exception $ex) {
                $this->addFlash('error', 'Ошибка при добавлении пользователя: ' . $ex->getMessage());
            }
        }

        $groups = $this->em->getRepository(Group::class)->findAll();

        return $this->render('pages/user/edit.html.twig', [
            'form' => $form->createView(),
            'action' => 'create',
            'groups' => $groups
        ]);
    }

    /**
     * @Route("/admin/users/{id}/remove", name="app_settings_users_remove")
     *
     * @Security("has_role('ROLE_APP_USER_DELETE')")
     *
     * @param User $user
     * @return Response
     * @throws \LogicException
     */
    public function removeAction(User $user): Response
    {
        $this->em->remove($user);
        $this->em->flush();

        $this->addFlash('success', 'Пользователь удален');

        return $this->redirectToRoute('app_settings_users_list');
    }
}