<?php

namespace App\Controller\Admin;

use App\Entity\FollowedUser;
use App\Entity\Repository\FollowedUserRepository;
use App\Form\FilterFollowedUsersType;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FollowedUserController extends BaseController
{
    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * @Route("/admin/followed-users", methods={"GET"}, name="app_followed_users_list")
     * @Security("is_granted('ROLE_FOLLOWED_USER_LIST')")
     *
     * @param Request $request
     * @return Response
     * @throws DBALException
     */
    public function listAction(Request $request)
    {
        $page    = $request->get('_page', 1);
        $perPage = $request->get('_per_page', 32);
        $offset  = ($page - 1) * $perPage;

        $form = $this->createForm(FilterFollowedUsersType::class);
        $form->handleRequest($request);

        $filter = $form->getData();
        $items  = [];

        if (!empty($filter['email'])) {

            /** @var FollowedUserRepository $repository */
            $repository = $this->em->getRepository(FollowedUser::class);
            $items = $repository->findFollowedUsers(strtolower($filter['email']), $perPage, $offset);
        }

        return $this->render('pages/followed_users/list.html.twig', [
            'form'  => $form->createView(),
            'items' => $items,
            'pager'            => [
                '_per_page'    => $perPage,
                '_page'        => $page,
                '_has_more'    => \count($items) >= $perPage
            ]
        ]);
    }
}