<?php

namespace App\Controller\Admin;

use App\DataSource\EmployeeOfferDataSource;
use App\DataSource\OwnerOfferDataSource;
use App\DataSource\SellerOfferDataSource;
use App\Entity\User;
use App\Lib\Enum\OfferExecutionStatusEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StatisticController extends BaseController
{
    /** @var EmployeeOfferDataSource */
    protected $employeeOfferDataSource;

    /** @var SellerOfferDataSource */
    protected $sellerOfferDataSource;

    /** @var OwnerOfferDataSource */
    protected $ownerOfferDataSource;

    /** @var  EntityManagerInterface */
    protected $em;

    public function __construct(
        EmployeeOfferDataSource $eds,
        SellerOfferDataSource $sds,
        OwnerOfferDataSource $ods,
        EntityManagerInterface $em
    ) {
        $this->employeeOfferDataSource  = $eds;
        $this->sellerOfferDataSource    = $sds;
        $this->ownerOfferDataSource     = $ods;
        $this->em                       = $em;
    }

    /**
     * @Route("/admin/statistic", name="app_stat_list")
     *
     * @Security("has_role('ROLE_APP_STAT_LIST')")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \LogicException
     * @throws \UnexpectedValueException
     * @throws \App\Exception\Api\DataSourceException
     */
    public function listAction(Request $request, UserGroupManager $userGroupManager): Response
    {
        $users = [];

        if ($userId = $request->get('user')) {

            /** @var User $user */
            $user = $this->em->getRepository(User::class)->findOneById($userId);
            if (null === $user) {
                $this->addFlash('error', 'Пользователь не обнаружен');
                $user = $this->getUser();
            }

            // а можем получать стату за данного пользователя?
            if (!$this->isGranted('ROLE_SUPER_ADMIN') && $user->getId() !== $this->getUser()->getId()) {
                $this->addFlash('warning', 'Доступен просмотр только своей статистики');
                $user = $this->getUser();
            }

        } else {
            $user  = $this->getUser();
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {

            $users = $this->em
                ->createQueryBuilder()
                ->select('u.id, u.email, g.name')
                ->from(User::class, 'u')
                ->innerJoin('u.groups', 'g')
                ->where('g.code IN (:code)')
                ->setParameter(':code', [UserGroupEnum::SELLER, UserGroupEnum::OWNER(), UserGroupEnum::EMPLOYEE])
                ->getQuery()
                ->getResult();
        }

        try {
            $status = new OfferExecutionStatusEnum($request->get('status'));
        } catch (\Exception $ex) {
            $status = new OfferExecutionStatusEnum(OfferExecutionStatusEnum::COMPLETE);
        }

        $items  = [];

        if ($userGroupManager->hasGroup($user, UserGroupEnum::OWNER())) {
            $items = $this->ownerOfferDataSource->getExecutionStatistic($user, $status);
        }

        if ($userGroupManager->hasGroup($user, UserGroupEnum::SELLER())) {
            $items = $this->sellerOfferDataSource->getExecutionStatistic($user, $status);
        }

        if ($userGroupManager->hasGroup($user, UserGroupEnum::EMPLOYEE())) {
            $items = $this->employeeOfferDataSource->getExecutionStatistic($user, $status);
        }

        return $this->render('pages/statistic/list.html.twig', [
            'items'        => $items,
            'statusList'   => [
                OfferExecutionStatusEnum::COMPLETE   => 'Исполнено',
                OfferExecutionStatusEnum::PROCESSING => 'В процессе',
                OfferExecutionStatusEnum::REJECTED   => 'Отклонено',
            ],
            'status'       => $status,
            'users'        => $users,
            'user'         => $user
        ]);
    }
}