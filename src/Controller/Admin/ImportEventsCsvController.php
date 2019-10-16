<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ImportEventsCsvController extends BaseController
{
    /**
     * @Route("/admin/import/events-csv", name="app_import_csv_list")
     *
     * @param Request $request
     */
    public function startAction(Request $request)
    {

    }
}