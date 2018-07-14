<?php

namespace App\Controller\Admin;

use App\DataSource\EmployeeOfferDataSource;
use App\DataSource\OwnerOfferDataSource;
use App\DataSource\SellerOfferDataSource;
use App\Lib\Enum\OfferExecutionStatusEnum;
use App\Lib\Enum\UserGroupEnum;
use App\Security\UserGroupManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StatisticController extends BaseController
{
    /** @var EmployeeOfferDataSource */
    protected $employeeOfferDataSource ;

    /** @var SellerOfferDataSource */
    protected $sellerOfferDataSource ;

    /** @var OwnerOfferDataSource */
    protected $ownerOfferDataSource ;

    public function __construct(EmployeeOfferDataSource $eds,
                                SellerOfferDataSource $sds,
                                OwnerOfferDataSource $ods)
    {
        $this->employeeOfferDataSource  = $eds;
        $this->sellerOfferDataSource    = $sds;
        $this->ownerOfferDataSource     = $ods;
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
        $status = new OfferExecutionStatusEnum($request->get('status', OfferExecutionStatusEnum::COMPLETE));
        $items  = [];

        if ($userGroupManager->hasGroup($this->getUser(), UserGroupEnum::OWNER())) {
            $items = $this->ownerOfferDataSource->getExecutionStatistic($this->getUser(), $status);
        }

        if ($userGroupManager->hasGroup($this->getUser(), UserGroupEnum::SELLER())) {
            $items = $this->sellerOfferDataSource->getExecutionStatistic($this->getUser(), $status);
        }

        if ($userGroupManager->hasGroup($this->getUser(), UserGroupEnum::EMPLOYEE())) {
            $items = $this->employeeOfferDataSource->getExecutionStatistic($this->getUser(), $status);
        }

        return $this->render('pages/statistic/list.html.twig', ['items' => $items, 'status' => $status]);
    }
}