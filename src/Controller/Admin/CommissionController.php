<?php

namespace App\Controller\Admin;

use App\Entity\Group;
use App\Entity\SellerBaseCommission;
use App\Entity\User;
use App\Enum\UserGroupEnum;
use App\Form\UserType;
use App\Manager\UserManager;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommissionController extends BaseController
{
    /** @var  EntityManagerInterface */
    protected $em;

    /** @var  UserManager  */
    protected $userManager;

    public function __construct(
        EntityManagerInterface $em,
        UserManager $userManager
    )
    {
        $this->em = $em;
        $this->userManager = $userManager;
    }

    /**
     * @Route("/admin/commissions/base", name="app_commissions_base_seller_edit")
     *
     * @Security("has_role('ROLE_APP_COMMISSIONS_EDIT')")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function editBaseSellerCommissionAction(Request $request): JsonResponse
    {
//        $this->em->getRepository(SellerBaseCommission::class)->findOneBy()
//
//        $form = $this->createForm(UserType::class, $user);
//
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            try {
//                $this->em->persist($user);
//                $this->em->flush();
//
//                $this->addFlash('success', 'Пользователь обновлен');
//
//                return $this->redirectToRoute('app_settings_users_list');
//
//            } catch (\Exception $ex) {
//                $this->addFlash('error', 'Ошибка при обновлении пользователя: ' . $ex->getMessage());
//            }
//        }
//
//        $groups = $this->em->getRepository(Group::class)->findAll();
//
//        return $this->render('pages/user/edit.html.twig', [
//            'form' => $form->createView(),
//            'action' => 'edit',
//            'groups' => $groups
//        ]);
    }
}