<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * SidebarController.
 */
class DashboardController extends BaseController
{
    /**
     * @Route("/admin/dashboard", name="app_dashboard")
     *
     * @param Request $request Request
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        return $this->render('pages/dashboard.html.twig', []);
    }
}
