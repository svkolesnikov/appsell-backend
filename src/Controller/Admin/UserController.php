<?php

namespace App\Controller\Admin;

use App\Entity\ForUserCommission;
use App\Entity\Group;
use App\Entity\User;
use App\Lib\Enum\UserGroupEnum;
use App\Form\UserType;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends BaseController
{
    /** @var  EntityManagerInterface */
    protected $em;

    /** @var  UserGroupManager  */
    protected $userGroupManager;

    public function __construct(EntityManagerInterface $em, UserGroupManager $userGroupManager
    )
    {
        $this->em = $em;
        $this->userGroupManager = $userGroupManager;
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
        $page        = $request->get('_page', 1);
        $perPage     = $request->get('_per_page', 16);
        $offset      =  ($page-1) * $perPage;
        $commissions = [];

        $qb = $this->em
            ->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        // "Работодатель" видит только своих сотрудников
        if ($this->userGroupManager->hasGroup($this->getUser(), UserGroupEnum::SELLER())) {
            $qb->innerJoin('u.profile', 'p')
                ->where('p.employer = :user')
                ->setParameter(':user', $this->getUser());
        }

        $users = $qb->getQuery()->getResult();

        $byUser = $this->isGranted('ROLE_ADMIN') ? null : $this->getUser();
        foreach ((array) $users as $user) {
            $commissions[$user->getId()] = $this->em
                ->getRepository(ForUserCommission::class)
                ->findOneBy(['user' => $user, 'by_user' => $byUser]);
        }

        return $this->render('pages/user/list.html.twig', [
            'commissions'   => $commissions,
            'users'         => $users,
            'pager'         => [
                '_per_page' => $perPage,
                '_page'     => $page,
                '_has_more' => \count($users) >= $perPage
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
     * @throws \App\Exception\AppException
     */
    public function createAction(Request $request): Response
    {
        $user = new User();

        if ($this->userGroupManager->hasGroup($this->getUser(), UserGroupEnum::SELLER())) {
            $user->getProfile()->setEmployer($this->getUser());
            $this->userGroupManager->addGroup($user, UserGroupEnum::EMPLOYEE());
        }

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
        try {

            $this->em->remove($user);
            $this->em->flush();

            $this->addFlash('success', 'Пользователь удален');

        } catch (\Exception $ex) {
            $this->addFlash('error', 'Ошибка при удалении пользователя: ' . $ex->getMessage());
        }

        return $this->redirectToRoute('app_settings_users_list');
    }

    /**
     * @Route("/admin/users/profile", name="app_user_profile")
     *
     * @Security("has_role('ROLE_APP_USER_PROFILE')")
     *
     * @return Response
     */
    public function profileAction(): Response
    {
        return $this->render('pages/user/profile.html.twig', ['user' => $this->getUser()]);
    }
}