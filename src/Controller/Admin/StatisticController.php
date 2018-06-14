<?php

namespace App\Controller\Admin;

use App\Entity\Offer;
use App\Form\OfferType;
use App\Manager\OfferManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StatisticController extends BaseController
{
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
     */
    public function listAction(Request $request): Response
    {
        return $this->render('pages/statistic/list.html.twig');
    }
}